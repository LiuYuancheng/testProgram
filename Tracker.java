
public class Tracker {
	public static void main(String[] args) {
		if (args.length != 3) {
			System.out.println("Usage: java Tracker [port] [N] [K]");
			return;
		}

		int port = Integer.parseInt(args[0]);
		int N = Integer.parseInt(args[1]);
		int K = Integer.parseInt(args[2]);

		final TrackerImpl tracker = new TrackerImpl(port, N, K);
		tracker.start();

		Runtime.getRuntime().addShutdownHook(new Thread(new Runnable() {
			public void run() {
				System.out.println("main() !Interrupted!");
				tracker.stop();
			}
		}));
	}
}
