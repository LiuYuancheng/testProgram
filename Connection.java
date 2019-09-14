import java.io.IOException;
import java.net.InetSocketAddress;
import java.nio.ByteBuffer;
import java.nio.channels.SocketChannel;

interface ConnectionListenerI {
	public abstract void onDisconnected(Connection connection);

	public abstract void onData(Connection connection, ByteBuffer buffer);
}

public class Connection {
	private static Logger logger = new Logger("Connection");

	public Connection(SocketChannel socket) {
		socket_ = socket;
		try {
			remote_host_ = ((InetSocketAddress) socket_.getRemoteAddress()).getHostString();
			remote_port_ = ((InetSocketAddress) socket_.getRemoteAddress()).getPort();
		} catch (IOException e) {
			e.printStackTrace();
		}
		read_buffer_ = ByteBuffer.allocate(1024 * 1024);
	}

	public Connection(String remote_host, int remote_port) {
		socket_ = null;
		remote_host_ = remote_host;
		remote_port_ = remote_port;
		read_buffer_ = ByteBuffer.allocate(1024 * 1024);
	}

	public void set_listener(ConnectionListenerI listener) {
		listener_ = listener;
	}

	public String getRemoteHost() {
		return remote_host_;
	}

	public int getRemotePort() {
		return remote_port_;
	}

	public String getRemoteAddress() {
		return String.format("%s:%s", getRemoteHost(), getRemotePort());
	}

	public SocketChannel getSocket() {
		return socket_;
	}

	public void write(Message msg) {
		msg.getBuffer().rewind();
		try {
			logger.log("write",
					String.format("size[%s] remote[%s] %s", msg.getBuffer().remaining(), getRemoteAddress(), msg));
			while (msg.getBuffer().hasRemaining()) {
				socket_.write(msg.getBuffer());
			}
		} catch (Exception e) {
			logger.log("write", "write() failed");
		}
	}

	public void read() {
		int num = -1;
		try {
			num = socket_.read(read_buffer_);
		} catch (IOException ex) {
			// ex.printStackTrace();
		}

		if (num == 0) {
			return;
		} else if (num == -1) {
			logger.log("read", String.format("connection closed by remote[%s]", getRemoteAddress()));
			if (listener_ != null)
				listener_.onDisconnected(this);
		} else {
			logger.log("read", String.format("size[%s] remote[%s]", num, getRemoteAddress()));

			read_buffer_.rewind();
			while (read_buffer_.position() < num) {
				int message_len = read_buffer_.getInt();
				logger.log("read", String.format("message_len[%s]", message_len));
				if (message_len == 0 || message_len + read_buffer_.position() - 4 > num)
					break;

				byte[] buffer = new byte[message_len];
				read_buffer_.position(read_buffer_.position() - 4);
				read_buffer_.get(buffer, 0, message_len);

				if (listener_ != null)
					listener_.onData(this, ByteBuffer.wrap(buffer));
			}
			if (read_buffer_.position() < num) {
				int i = 0;
				for (i = 0; i < num - read_buffer_.position(); ++i) {
					read_buffer_.put(0, read_buffer_.get(read_buffer_.position() + i));
				}
				read_buffer_.position(i);
			} else {
				read_buffer_.rewind();
			}

			logger.log("read", String.format("buffer_position[%s]", read_buffer_.position()));
		}
	}

	private SocketChannel socket_;
	private String remote_host_;
	private int remote_port_;
	private ByteBuffer read_buffer_;

	private ConnectionListenerI listener_;
}
