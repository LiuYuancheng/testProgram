#ifndef TEXTFINDER_H
#define TEXTFINDER_H

#include <QMainWindow>

namespace Ui {
class TextFinder;
}

class TextFinder : public QMainWindow
{
    Q_OBJECT

public:
    explicit TextFinder(QWidget *parent = nullptr);
    ~TextFinder();

private slots:
    void on_pushButton_clicked();

private:
    Ui::TextFinder *ui;
    void loadTextFile();
};

#endif // TEXTFINDER_H
