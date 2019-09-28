import javax.swing.*;
import java.awt.*;
import java.util.ArrayList;

public class GameUI extends JFrame implements PlaygroundListenerI {
	private static final long serialVersionUID = -4407424276798218633L;

	GameInfoPanel infoPanel;
	final JPanel playerListPanel = new JPanel();
	GameBoard board;

	public GameUI(int N, String player_id) {
		super("Maze Game");
		final int cell_size = 30;
		
		setSize(260 + cell_size * N, 90 + cell_size * N);
		setResizable(false);

		board = new GameBoard(N, cell_size * N, cell_size * N);
		infoPanel = new GameInfoPanel(player_id);

		infoPanel.setBackground(Color.CYAN);
		playerListPanel.setBackground(Color.BLUE);

		infoPanel.setSize(260 + cell_size * N, 30);
		playerListPanel.setSize(200, 20 + cell_size * N);

		add(infoPanel);
		add(playerListPanel);
		add(board);

		getContentPane().setLayout(null);
		infoPanel.setLocation(0, 0);
		playerListPanel.setLocation(60 + cell_size * N, 30);
		board.setLocation(30, 40);

		setVisible(true);
		setDefaultCloseOperation(WindowConstants.EXIT_ON_CLOSE);
	}

	@Override
	public void onUpdate(MazeStateMsg msg) {
		SwingUtilities.invokeLater(new Runnable() {
			public void run() {
				System.out.println("GameUI::onUpdate(MazeStateMsg)");
				board.update(msg);
				board.refresh();
				revalidate();
			}
		});
	}

	@Override
	public void onUpdate(PlayersStateMsg msg) {
		final ArrayList<PlayerState> players = msg.clone();
		SwingUtilities.invokeLater(new Runnable() {
			public void run() {
				System.out.println("GameUI::onUpdate(PlayerStateMsg)");
				playerListPanel.removeAll();
				for (PlayerState ps : players) {
					PlayerInfoPanel playerInfoPanel = new PlayerInfoPanel("Player <" + ps.id + ">", ps.treasure, 0);
					playerInfoPanel.setBackground(Color.yellow);
					playerInfoPanel.setSize(150, 300);

					playerListPanel.add(playerInfoPanel);
				}
				board.update(players);
				revalidate();
			}
		});
	}
}