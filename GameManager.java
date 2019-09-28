import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.nio.ByteBuffer;
import java.util.ArrayList;

public class GameManager implements ServerSocketListenerI, ConnectionListenerI, Runnable {
	private static Logger logger = new Logger("GameManager");

	public GameManager(String tracker_host, int tracker_port, String player_id) {
		tracker_host_ = tracker_host;
		tracker_port_ = tracker_port;
		player_id_ = player_id;

		server_ = new ConnectionManager(0, this);

		role_manager_ = new PlayerManager(this);
		player_list_ = new ArrayList<Player>();
	}

	public String getPlayerId() {
		return player_id_;
	}

	public String getLocalHost() {
		return server_.getLocalHost();
	}

	public int getListeningPort() {
		return server_.getListeningPort();
	}

	public boolean start() {
		if (!server_.start())
			return false;

		if (connectTracker()) {
			logger.log("start", "connected tracker");
			running_ = true;
			thread_ = new Thread(this);
			thread_.start();
			return true;
		}
		return false;
	}

	public void stop() {
		running_ = false;
		if (thread_ != null)
			thread_.interrupt();
		synchronized (this) {
			server_.stop();
			for (Player player : player_list_) {
				player.stop();
				player.getConnection().set_listener(null);
			}
			if (tracker_ != null)
				server_.close(tracker_);
			tracker_ = null;
		}
	}

	public void run() {
		logger.log("run", "started");
		BufferedReader br = new BufferedReader(new InputStreamReader(System.in));
		while (running_) {
			try {
				while (!br.ready()) {
					Thread.sleep(10);
				}
				String line = br.readLine();
				if (line.isEmpty())
					continue;

				char direction = line.charAt(0);
				logger.log("run", String.format("move direction[%s]", direction));
				synchronized (this) {
					role_manager_.move(direction);
				}

				if (direction == '9')
					break;
			} catch (IOException | InterruptedException e) {
				// e.printStackTrace();
			}
		}
		try {
			br.close();
		} catch (IOException e) {
		}
		logger.log("run", "stopped");
	}

	@Override
	public void onAccepted(Connection connection) {
		Player player = new Player(connection, this);
		player.start();
		synchronized (this) {
			player_list_.add(player);
		}
	}

	@Override
	public void onDisconnected(Connection connection) {
		synchronized (this) {
			server_.close(connection);
			tracker_ = null;
			role_manager_.onTrackerDown();
		}
	}

	@Override
	public void onData(Connection connection, ByteBuffer buffer) {
		logger.log("onData", "");
		buffer.getInt(); // len
		MsgType msg_type = MsgType.values()[buffer.getInt()];
		switch (msg_type) {
		case kInfo:
			InfoMsg msg = new InfoMsg(buffer);
			if (msg.deserialize()) {
				synchronized (this) {
					role_manager_.handle(msg);
				}
			}
			break;
		default:
			logger.log("onData", String.format("unexpected msg_type[%s]", msg_type));
			break;
		}
	}

	public void startGUI(int N) {
		gui_ = new GameUI(N, getPlayerId());
	}

	public void updateGUI(MazeStateMsg msg) {
		gui_.onUpdate(msg);
	}

	public void updateGUI(PlayersStateMsg msg) {
		gui_.onUpdate(msg);
	}

	public Player getPlayer(String host, int listening_port) {
		synchronized (this) {
			for (Player player : player_list_) {
				if (player.getState().host.equals(host) && player.getState().listening_port == listening_port) {
					return player;
				}
			}
		}
		return null;
	}

	public Connection getTracker() {
		return tracker_;
	}

	public boolean connectTracker() {
		if (tracker_ == null) {
			tracker_ = server_.connect(tracker_host_, tracker_port_);
			if (tracker_ == null) {
				stop();
				return false;
			} else {
				tracker_.set_listener(this);
				JoinMsg msg = new JoinMsg(getPlayerId(), getLocalHost(), getListeningPort(), 0);
				msg.serialize();
				tracker_.write(msg);
			}
		}
		return true;
	}

	public void disconnectTracker() {
		if (tracker_ != null) {
			server_.close(tracker_);
			tracker_ = null;
			logger.log("disconnectTracker", "");
		}
	}

	public void reportQuitPlayer(String host, int listening_port) {
		PeerQuitMsg msg = new PeerQuitMsg(host, listening_port);
		msg.serialize();
		if (connectTracker())
			tracker_.write(msg);
	}

	public Player connect(String host, int port) {
		Connection connection = server_.connect(host, port);
		if (connection != null) {
			Player player = new Player(connection, this);
			player.setState(new PlayerState());
			player.getState().host = host;
			player.getState().listening_port = port;
			connection.set_listener(player);
			player.start();
			return player;
		} else {
			return null;
		}
	}

	public void promotePrimary(PlayerManager pm) {
		logger.log("promotePrimary", "from normal player");
		PrimaryManager new_rm = new PrimaryManager(this);
		new_rm.promote(pm);
		role_manager_ = new_rm;
	}

	public void promotePrimary(SecondaryManager sm) {
		logger.log("promotePrimary", "from secondary server");
		PrimaryManager new_rm = new PrimaryManager(this);
		new_rm.promote(sm);
		role_manager_ = new_rm;
	}

	public void promoteSecondary(PlayerManager pm) {
		logger.log("promoteSecondary", "");
		SecondaryManager new_rm = new SecondaryManager(this);
		role_manager_ = new_rm;
		new_rm.promote(pm);
	}

	public void onDisconnected(Player player) {
		synchronized (this) {
			kickPlayer(player);
		}
	}

	public void handle(Player player, InfoMsg info) {
		if (info.deserialize()) {
			synchronized (this) {
				role_manager_.handle(info);
			}
		}
	}

	public void handle(Player player, JoinMsg msg) {
		synchronized (this) {
			if (msg.deserialize()) {
				role_manager_.handle(player, msg);
			} else {
				kickPlayer(player);
			}
		}
	}

	public void handle(Player player, PlayersStateMsg msg) {
		synchronized (this) {
			if (msg.deserialize()) {
				role_manager_.handle(player, msg);
			} else {
				kickPlayer(player);
			}
		}
	}

	public void handle(Player player, MazeStateMsg msg) {
		synchronized (this) {
			role_manager_.handle(msg);
		}
	}

	public void handle(Player player, MoveMsg msg) {
		synchronized (this) {
			if (msg.deserialize()) {
				role_manager_.handle(player, msg);
			} else {
				kickPlayer(player);
			}
		}
	}

	public void broadcast(InfoMsg info) {
		for (Player player : player_list_) {
			player.getConnection().write(info);
		}
	}

	public void kickPlayer(Player player) {
		player.stop();
		server_.close(player.getConnection());
		player_list_.remove(player);
		role_manager_.onDisconnected(player);
	}

	private String tracker_host_;
	private int tracker_port_;
	private String player_id_;

	private ConnectionManager server_;
	private Connection tracker_;

	private volatile boolean running_;
	private Thread thread_;

	private RoleManager role_manager_;
	private ArrayList<Player> player_list_;

	private GameUI gui_;
}
