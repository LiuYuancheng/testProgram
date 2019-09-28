import java.text.SimpleDateFormat;

public class Logger {
	private String class_name_;
	private SimpleDateFormat date_format_ = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSS");

	public Logger(String class_name) {
		class_name_ = class_name;
	}

	public void log(String method_name, String msg) {
		System.out.format("%s %s %s\n", date_format_.format(System.currentTimeMillis()),
				String.format("%s.%s()", class_name_, method_name), msg);
	}
}
