
public class Game {
	public static void main(String[] args) {
		if (args.length != 3) {
			System.out.println("Usage: java Game [tracker_ip] [tracker_port] [id]");
			return;
		}

		String ip = args[0];
		int port = Integer.parseInt(args[1]);
		String id = args[2];

		final GameManager manager = new GameManager(ip, port, id);
		manager.start();

		Runtime.getRuntime().addShutdownHook(new Thread(new Runnable() {
			public void run() {
				System.out.println("main() !Interrupted!");
				manager.stop();
			}
		}));
	}
}
