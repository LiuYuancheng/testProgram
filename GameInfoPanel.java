import javax.swing.*;

public class GameInfoPanel extends JPanel {
	private static final long serialVersionUID = 533014938563604479L;
	JLabel totalNumberOfPlayersLabel = new JLabel();

	public GameInfoPanel(String id) {
		totalNumberOfPlayersLabel.setText(id);
		add(totalNumberOfPlayersLabel);
	}
}
