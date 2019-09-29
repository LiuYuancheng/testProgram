import javax.swing.*;
import java.awt.*;
import java.util.ArrayList;

public class GameBoard extends JPanel {
	private static final long serialVersionUID = -4806535720184807495L;

	// set initial board size, width and height
	public GameBoard(int boardSize, int width, int height) {
		N_ = boardSize;
		grid_ = new JLabel[boardSize * boardSize];
		treasures_ = new ArrayList<Integer>();

		setSize(width, height);
		setBackground(new Color(100, 149, 237));
		setLayout(new GridLayout(boardSize, boardSize));

		for (int i = 0; i < (boardSize * boardSize); i++) {
			treasures_.add(0);
			grid_[i] = new JLabel();
			grid_[i].setBorder(BorderFactory.createLineBorder(Color.BLACK));
			add(grid_[i]);
		}
	}

	public void update(ArrayList<PlayerState> players) {
		players_ = players;
	}

	public void update(MazeStateMsg msg) {
		msg.getPlayground().clone(treasures_);
	}

	public void refresh() {
		for (int x = 0; x < N_ * N_; ++x) {
			JLabel l = grid_[x];
			l.setForeground(Color.BLACK);
			l.setText("" + treasures_.get(x));
		}

		for (PlayerState ps : players_) {
			JLabel l = grid_[ps.y * N_ + ps.x];
			l.setForeground(Color.RED);
			l.setText(ps.id);
		}
	}

	private int N_;
	private JLabel[] grid_;
	private ArrayList<PlayerState> players_;
	private ArrayList<Integer> treasures_;
}