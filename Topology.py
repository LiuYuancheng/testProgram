#!/usr/bin/python

"""
Topology of Organization with 3 switches(S1,S2 and S3) connected to corresponding hosts h1, h2 and h3.

Controller is configured to run on Localhost with tcp port:6633

Pravein, Aug 2016
"""

from mininet.net import Mininet
from mininet.node import Controller, OVSSwitch, RemoteController
from mininet.cli import CLI
from mininet.log import setLogLevel
import subprocess

def multiControllerNet():

    net = Mininet( controller=Controller, switch=OVSSwitch)

    print "*** Creating controllers. Make sure you run the controller at port 6633!!"
    ctrl = RemoteController( 'ctrl', ip='127.0.0.1',port=6633)

    print "*** Creating switches"
    s1 = net.addSwitch( 'S1' )
    s2 = net.addSwitch( 'S2' )
    s3 = net.addSwitch( 'S3' )
    print "*** Creating hosts"
    h1 = net.addHost('h1', mac='00:00:00:00:00:01');
    h2 = net.addHost('h2', mac='00:00:00:00:00:02');
    h3 = net.addHost('h3', mac='00:00:00:00:00:03');

    print "*** Creating links"
    net.addLink(s1, h1)
    net.addLink(s2, h2)
    net.addLink(s3, h3)

    net.addLink(s1,s2)
    net.addLink(s1,s3)
    net.addLink(s2,s3)

    print "*** Starting network"
    net.build()
    s1.start( [ ctrl ] )
    s2.start( [ ctrl ] )
    s3.start( [ ctrl ] )

    # Create Queues for the switches
    subprocess.Popen("./mininet_add_queue.py",shell=True);

    print "*** Running CLI"
    CLI( net )

    print "*** Stopping network"
    net.stop()

if __name__ == '__main__':
    setLogLevel( 'info' )  # for CLI output
    # Setup Queues in the switches
    multiControllerNet()
