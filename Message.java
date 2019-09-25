import java.nio.BufferUnderflowException;
import java.nio.ByteBuffer;
import java.util.ArrayList;

enum MsgType {
	kInfo, kPeerQuit, kJoin, kPlayerState, kMazeState, kMove
}

public abstract class Message {
	public Message(MsgType type) {
		type_ = type;
	}

	public ByteBuffer getBuffer() {
		return buffer_;
	}

	public void serialize() {
		buffer_ = ByteBuffer.allocate(1024 * 1024);
		buffer_.putInt(0); // len
		buffer_.putInt(type_.ordinal());
		serializeImpl();
		buffer_.putInt(0, buffer_.position());
		buffer_.flip();
	}

	public boolean deserialize() {
		buffer_.rewind();

		try {
			buffer_.getInt(); // len
			buffer_.getInt(); // type
			deserializeImpl();
			return true;
		} catch (BufferUnderflowException ex) {
			ex.printStackTrace();
			return false;
		}
	}

	protected abstract void serializeImpl();

	protected abstract void deserializeImpl();

	protected MsgType type_;
	protected ByteBuffer buffer_;
}

class MessageHelper {
	public static int putString(ByteBuffer buffer, String s) {
		buffer.putInt(s.length());
		for (char c : s.toCharArray()) {
			buffer.putChar(c);
		}
		return s.length() * 2 + 4;
	}

	public static String getString(ByteBuffer buffer) {
		int length = buffer.getInt();
		StringBuilder buf = new StringBuilder(length);
		for (int i = 0; i < length; ++i) {
			buf.append(buffer.getChar());
		}
		return buf.toString();
	}
}

class TrackerPeerInfo {
	public String host;
	public int listening_port;

	public void serialize(ByteBuffer buffer) {
		MessageHelper.putString(buffer, host);
		buffer.putInt(listening_port);
	}

	public void deserialize(ByteBuffer buffer) {
		host = MessageHelper.getString(buffer);
		listening_port = buffer.getInt();
	}
}

class PeerQuitMsg extends Message {
	public PeerQuitMsg(String host, int listening_port) {
		super(MsgType.kPeerQuit);
		host_ = host;
		listening_port_ = listening_port;
	}

	public PeerQuitMsg(ByteBuffer buffer) {
		super(MsgType.kPeerQuit);
		buffer_ = buffer;
	}

	String getHost() {
		return host_;
	}

	int getListeningPort() {
		return listening_port_;
	}

	protected void serializeImpl() {
		MessageHelper.putString(buffer_, host_);
		buffer_.putInt(listening_port_);
	}

	protected void deserializeImpl() {
		host_ = MessageHelper.getString(buffer_);
		listening_port_ = buffer_.getInt();
	}

	private String host_;
	private int listening_port_;
}

class InfoMsg extends Message {
	private static Logger logger = new Logger("InfoMsg");

	public InfoMsg(int N, int K) {
		super(MsgType.kInfo);
		N_ = N;
		K_ = K;
		peers_ = new ArrayList<TrackerPeerInfo>();
	}

	public InfoMsg(ByteBuffer buffer) {
		super(MsgType.kInfo);
		buffer_ = buffer;
		peers_ = new ArrayList<TrackerPeerInfo>();
	}

	public int getN() {
		return N_;
	}

	public int getK() {
		return K_;
	}

	public ArrayList<TrackerPeerInfo> getPeers() {
		return peers_;
	}

	public boolean addPeer(String host, int port) {
		for (TrackerPeerInfo info : peers_) {
			if (info.host.equals(host) && info.listening_port == port)
				return false;
		}

		logger.log("addPeer", "host[" + host + "] listening_port[" + port + "]");
		TrackerPeerInfo peer = new TrackerPeerInfo();
		peer.host = host;
		peer.listening_port = port;
		peers_.add(peer);
		serialize();
		return true;
	}

	public void removePeer(String host, int port) {
		for (TrackerPeerInfo info : peers_) {
			if (info.host.equals(host) && info.listening_port == port) {
				peers_.remove(info);
				logger.log("removePeer", "host[" + host + "] listening_port[" + port + "]");
				serialize();
				return;
			}
		}
	}

	public boolean has(String host, int port) {
		for (TrackerPeerInfo info : peers_) {
			if (info.host.equals(host) && info.listening_port == port)
				return true;
		}
		return false;
	}

	public void clearPeers() {
		peers_.clear();
		serialize();
	}

	@Override
	protected void serializeImpl() {
		buffer_.putInt(N_);
		buffer_.putInt(K_);

		buffer_.putInt(peers_.size());
		for (TrackerPeerInfo peer : peers_) {
			peer.serialize(buffer_);
		}
	}

	@Override
	protected void deserializeImpl() {
		N_ = buffer_.getInt();
		K_ = buffer_.getInt();

		int peer_count = buffer_.getInt();
		for (int i = 0; i < peer_count; ++i) {
			TrackerPeerInfo peer = new TrackerPeerInfo();
			peer.deserialize(buffer_);
			peers_.add(peer);
		}
	}

	private int N_;
	private int K_;
	private ArrayList<TrackerPeerInfo> peers_;
}

class JoinMsg extends Message {
	public JoinMsg(String id, String host, int port, int seq_num) {
		super(MsgType.kJoin);
		id_ = id;
		host_ = host;
		listening_port_ = port;
		seq_num_ = seq_num;
	}

	public JoinMsg(ByteBuffer buffer) {
		super(MsgType.kJoin);
		buffer_ = buffer;
	}

	public JoinMsg(JoinMsg msg) {
		super(MsgType.kJoin);
		id_ = msg.getId();
		listening_port_ = msg.getListeningPort();
		seq_num_ = msg.getSeqNum();
	}

	public String getId() {
		return id_;
	}

	public String getHost() {
		return host_;
	}

	public int getListeningPort() {
		return listening_port_;
	}

	public int getSeqNum() {
		return seq_num_;
	}

	@Override
	protected void serializeImpl() {
		MessageHelper.putString(buffer_, id_);
		MessageHelper.putString(buffer_, host_);
		buffer_.putInt(listening_port_);
		buffer_.putInt(seq_num_);
	}

	@Override
	protected void deserializeImpl() {
		id_ = MessageHelper.getString(buffer_);
		host_ = MessageHelper.getString(buffer_);
		listening_port_ = buffer_.getInt();
		seq_num_ = buffer_.getInt();
	}

	private String id_;
	private String host_;
	private int listening_port_;
	private int seq_num_;
}

class PlayerState {
	public PlayerState() {
		x = -1;
		y = -1;
		treasure = 0;
		last_seq_num = 0;
	}

	public PlayerState(String id, String host, int listening_port) {
		this();
		this.id = id;
		this.host = host;
		this.listening_port = listening_port;
	}

	public PlayerState(PlayerState ps) {
		x = ps.x;
		y = ps.y;
		treasure = ps.treasure;
		last_seq_num = ps.last_seq_num;
		id = ps.id;
		host = ps.host;
		listening_port = ps.listening_port;
	}

	public String id;
	public String host;
	public int listening_port;
	public int x;
	public int y;
	public int treasure;
	public int last_seq_num;

	public void serialize(ByteBuffer buffer) {
		MessageHelper.putString(buffer, id);
		MessageHelper.putString(buffer, host);
		buffer.putInt(listening_port);
		buffer.putInt(x);
		buffer.putInt(y);
		buffer.putInt(treasure);
		buffer.putInt(last_seq_num);
	}

	public void deserialize(ByteBuffer buffer) {
		id = MessageHelper.getString(buffer);
		host = MessageHelper.getString(buffer);
		listening_port = buffer.getInt();
		x = buffer.getInt();
		y = buffer.getInt();
		treasure = buffer.getInt();
		last_seq_num = buffer.getInt();
	}

	public String toString() {
		return String.format("id[%s] host[%s] listening_port[%s] x[%s] y[%s] treasure[%s] last_seq_num[%s]", id, host,
				listening_port, x, y, treasure, last_seq_num);
	}
}

class PlayersStateMsg extends Message {
	public PlayersStateMsg() {
		super(MsgType.kPlayerState);
		players_ = new ArrayList<PlayerState>();
	}

	public PlayersStateMsg(ByteBuffer buffer) {
		super(MsgType.kPlayerState);
		players_ = new ArrayList<PlayerState>();
		buffer_ = buffer;
	}

	public ArrayList<PlayerState> getPlayersState() {
		return players_;
	}

	public void addPlayer(PlayerState player) {
		for (PlayerState p : players_) {
			if (p.host.equals(player.host) && p.listening_port == player.listening_port)
				return;
		}

		players_.add(player);
	}

	public void removePlayer(String host, int listening_port) {
		for (PlayerState p : players_) {
			if (p.host.equals(host) && p.listening_port == listening_port) {
				players_.remove(p);
				break;
			}
		}
	}

	public boolean has(String host, int listening_port) {
		for (PlayerState p : players_) {
			if (p.host.equals(host) && p.listening_port == listening_port)
				return true;
		}
		return false;
	}

	public void consolidate(InfoMsg info) {
		ArrayList<PlayerState> to_remove_players = new ArrayList<PlayerState>();
		for (PlayerState ps : players_) {
			if (!info.has(ps.host, ps.listening_port))
				to_remove_players.add(ps);
		}
		for (PlayerState i : to_remove_players) {
			players_.remove(i);
		}
	}

	public ArrayList<PlayerState> clone() {
		ArrayList<PlayerState> arr = new ArrayList<PlayerState>();
		for (PlayerState ps : players_) {
			arr.add(new PlayerState(ps));
		}
		return arr;
	}

	@Override
	protected void serializeImpl() {
		buffer_.putInt(players_.size());
		for (PlayerState p : players_) {
			p.serialize(buffer_);
		}
	}

	private static Logger logger = new Logger("PlayersStateMsg");
	@Override
	protected void deserializeImpl() {
		int size = buffer_.getInt();
		for (int i = 0; i < size; ++i) {
			PlayerState p = new PlayerState();
			p.deserialize(buffer_);
			players_.add(p);
			logger.log("deserializeImpl", p.toString());
		}
	}

	private ArrayList<PlayerState> players_;
}

class MazeStateMsg extends Message {
	public MazeStateMsg(ByteBuffer buffer) {
		super(MsgType.kMazeState);
		buffer_ = buffer;
	}

	public MazeStateMsg(Playground playground) {
		super(MsgType.kMazeState);
		playground_ = playground;
	}

	public Playground getPlayground() {
		return playground_;
	}

	public void setPlayground(Playground playground) {
		playground_ = playground;
	}

	@Override
	protected void serializeImpl() {
		playground_.serialize(buffer_);
	}

	@Override
	protected void deserializeImpl() {
		playground_.deserialize(buffer_);
	}

	private Playground playground_;
}

class MoveMsg extends Message {
	public MoveMsg(ByteBuffer buffer) {
		super(MsgType.kMove);
		buffer_ = buffer;
	}

	public MoveMsg(char direction, int seq_num) {
		super(MsgType.kMove);
		direction_ = direction;
		seq_num_ = seq_num;
	}

	char getDirection() {
		return direction_;
	}

	int getSeqNum() {
		return seq_num_;
	}

	@Override
	protected void serializeImpl() {
		buffer_.putChar(direction_);
		buffer_.putInt(seq_num_);
	}

	@Override
	protected void deserializeImpl() {
		direction_ = buffer_.getChar();
		seq_num_ = buffer_.getInt();
	}

	private char direction_;
	private int seq_num_;
}
