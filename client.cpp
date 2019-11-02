//   Request-reply client in C++
//   Connects REQ socket to tcp://localhost:5559
//   Sends "Hello" to server, expects "World" back
//
// Olivier Chamoux <olivier.chamoux@fr.thalesgroup.com>

#include "zhelpers.hpp"
#include <stdlib.h>
using namespace std;
int main (int argc, char *argv[])
{
cout << "111"  << endl;
    zmq::context_t context(1);
cout << "222" << endl;
    zmq::socket_t requester(context, ZMQ_REQ);
cout << "333" << endl;
    requester.connect("tcp://localhost:5559");
cout << "444" << endl;
    for( int request = 0 ; request < 10 ; request++) {
        cout << "555" << endl;
        s_send (requester, "Hello");
cout << "666" << endl;
        std::string string = s_recv (requester);
        cout << "777" << endl;
        std::cout << "Received reply " << request
                << " [" << string << "]" << std::endl;
cout << "888" << endl;
    }
}

