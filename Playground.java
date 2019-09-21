import java.nio.ByteBuffer;
import java.util.ArrayList;

class Node {
	public Node(int x0, int y0) {
		x = x0;
		y = y0;
		treasure = 0;
		player = null;
	}

	public final int x;
	public final int y;
	public int treasure;
	public PlayerState player;
}

public class Playground {
	private static Logger logger = new Logger("Playground");

	public Playground(int N, int K) {
		N_ = N;
		K_ = K;
		yard_ = new Node[N][N];
		for (int i = 0; i < N; ++i) {
			for (int j = 0; j < N; ++j) {
				yard_[i][j] = new Node(j, i);
			}
		}

		for (int i = 0; i < K_; ++i) {
			int j = (int) (Math.random() * N_ * N_);
			++yard_[j / N_][j % N_].treasure;
		}
	}

	public void clone(ArrayList<Integer> treasures) {
		for (int y = 0; y < N_; ++y) {
			for (int x = 0; x < N_; ++x) {
				treasures.set(y * N_ + x, yard_[y][x].treasure);
			}
		}
	}

	public void initPlayer(PlayerState player) {
		while (player.x == -1) {
			int j = (int) (Math.random() * N_ * N_);
			int x = j % N_;
			int y = j / N_;
			if (yard_[y][x].player == null) {
				enter(player, yard_[y][x]);
				logger.log("initPlayer", player.toString());
				break;
			}
		}
	}

	public void setPlayer(int x, int y, PlayerState player) {
		yard_[y][x].player = player;
		logger.log("setPlayer", String.format("x[%s] y[%s] %s", x, y, player));
	}

	public boolean moveWest(PlayerState player) {
		initPlayer(player);
		if (player.x == 0) {
			logger.log("moveWest", String.format("invalid move(<0) player[%s]", player.id));
			return false;
		}
		if (yard_[player.y][player.x - 1].player != null) {
			logger.log("moveWest", String.format("invalid move(O) player[%s]", player.id));
			return false;
		}
		enter(player, yard_[player.y][player.x - 1]);
		logger.log("moveWest", player.toString());
		return true;
	}

	public boolean moveEast(PlayerState player) {
		initPlayer(player);
		if (player.x >= N_ - 1) {
			logger.log("moveEast", String.format("invalid move(>N) player[%s]", player.id));
			return false;
		}
		if (yard_[player.y][player.x + 1].player != null) {
			logger.log("moveEast", String.format("invalid move(O) player[%s]", player.id));
			return false;
		}
		enter(player, yard_[player.y][player.x + 1]);
		logger.log("moveEast", player.toString());
		return true;
	}

	public boolean moveNorth(PlayerState player) {
		initPlayer(player);
		if (player.y == 0) {
			logger.log("moveNorth", String.format("invalid move(<0) player[%s]", player.id));
			return false;
		}
		if (yard_[player.y - 1][player.x].player != null) {
			logger.log("moveNorth", String.format("invalid move(O) player[%s]", player.id));
			return false;
		}
		enter(player, yard_[player.y - 1][player.x]);
		logger.log("moveNorth", player.toString());
		return true;
	}

	public boolean moveSouth(PlayerState player) {
		initPlayer(player);
		if (player.y >= N_ - 1) {
			logger.log("moveSouth", String.format("invalid move(>N) player[%s]", player.id));
			return false;
		}
		if (yard_[player.y + 1][player.x].player != null) {
			logger.log("moveSouth", String.format("invalid move(O) player[%s]", player.id));
			return false;
		}
		enter(player, yard_[player.y + 1][player.x]);
		logger.log("moveSouth", player.toString());
		return true;
	}

	public void serialize(ByteBuffer buffer) {
		for (int i = 0; i < N_; ++i) {
			for (int j = 0; j < N_; ++j) {
				buffer.putInt(yard_[i][j].treasure);
			}
		}
	}

	public void deserialize(ByteBuffer buffer) {
		for (int i = 0; i < N_; ++i) {
			for (int j = 0; j < N_; ++j) {
				yard_[i][j].treasure = buffer.getInt();
				// System.out.print(yard_[i][j].treasure + " ");
			}
			// System.out.println();
		}
	}

	private void enter(PlayerState player, Node node) {
		if (player.x != -1 && player.y != -1)
			yard_[player.y][player.x].player = null;

		player.x = node.x;
		player.y = node.y;
		player.treasure += node.treasure;

		for (int i = 0; i < node.treasure;) {
			int j = (int) (Math.random() * N_ * N_);
			Node node0 = yard_[j / N_][j % N_];
			if (node0.player == null && node0.x != node.x && node0.y != node.y) {
				++node0.treasure;
				++i;
			}
		}
		node.player = player;
		node.treasure = 0;

		/*
		 * for (int i = 0; i < N_; ++i) { for (int j = 0; j < N_; ++j) {
		 * System.out.print(yard_[i][j].treasure + " "); } System.out.println();
		 * }
		 */
	}

	private int N_;
	private int K_;
	private Node[][] yard_;
}
