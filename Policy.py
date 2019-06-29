#!/usr/bin/python

"""
@Author <LiuYuancheng/A0159354X>
Date :
"""


import httplib
import json
import os
import subprocess
import sys


class flowStat(object):
    def __init__(self, server):
        self.server = server

    def get(self, switch):
        ret = self.rest_call({}, 'GET', switch)
        return json.loads(ret[2])

    def rest_call(self, data, action, switch):
        path = '/wm/core/switch/'+switch+"/flow/json"
        headers = {
            'Content-type': 'application/json',
            'Accept': 'application/json',
            }
        body = json.dumps(data)
        conn = httplib.HTTPConnection(self.server, 8080)
        #print path
        conn.request(action, path, body, headers)
        response = conn.getresponse()
        ret = (response.status, response.reason, response.read())
        conn.close()
        return ret

class StaticFlowPusher(object):
    def __init__(self, server):
        self.server = server

    def get(self, data):
        ret = self.rest_call({}, 'GET')
        return json.loads(ret[2])

    def set(self, data):
        ret = self.rest_call(data, 'POST')
        return ret[0] == 200

    def remove(self, objtype, data):
        ret = self.rest_call(data, 'DELETE')
        return ret[0] == 200

    def rest_call(self, data, action):
        path = '/wm/staticflowpusher/json'
        headers = {
            'Content-type': 'application/json',
            'Accept': 'application/json',
            }
        body = json.dumps(data)
        conn = httplib.HTTPConnection(self.server, 8080)
        conn.request(action, path, body, headers)
        response = conn.getresponse()
        ret = (response.status, response.reason, response.read())
        print ret
        conn.close()
        return ret

# Create Objects for flow pusher, and stats
pusher = StaticFlowPusher('127.0.0.1')
stat =  flowStat('127.0.0.1')

# To insert the policies for the traffic applicable to path between S1 and S2
def H1toH2():
    #Limit the data flow radar between H1 and H2 to 1Mbps
    # limit S1 to eth2
    subprocess.Popen("ovs-vsctl -- set port S1-eth2 other-config:max-rate=1000000", shell=True)
    # limit S2 to eth2
    subprocess.Popen("ovs-vsctl -- set port S2-eth2 other-config:max-rate=1000000", shell=True)
    pass

# To insert the policies for the traffic applicable to path between S2 and S3
def H2toH3():
    #Policy 1: Block all traffic using UDP port from 1000-1100 with high priority = 100
    for i in range(1000,1100):
        port = str(i)
        S2toS3UDPBlock = {'switch': "00:00:00:00:00:00:00:02", "name": "S2h2toh3UDP", "cookie": "0",
                            "priority": "100", "in_port": "1", "eth_type": "0x800", 'src-ip': "10.0.0.2/32",
                            "dst-ip": "10.0.0.3", "nw-proto": "UDP","tp-src": port, "priority": "10", "action": "DENY"}
        S3toS2UDPBlock = {'switch': "00:00:00:00:00:00:00:03", "name": "S3h3toh2UDP", "cookie": "0",
                            "priority": "100", "in_port": "1", "eth_type": "0x800", 'src-ip': "10.0.0.3/32",
                            "dst-ip": "10.0.0.2", "nw-proto": "UDP","tp-src": port, "priority": "10", "action": "DENY"}
        pusher.set(S2toS3UDPBlock)
        pusher.set(S3toS2UDPBlock)
        # Add the firewall DENY rule also
        subprocess.Popen("curl -X POST -d '{\"src-ip\": \"10.0.0.2/32\", \"nw-proto\":\"UDP\",tp-src\":\""+port+"\",\"priority\":\"100\", \"action\":\"DENY\" }' http://localhost:8080/wm/firewall/rules/json | python -m json.tool",shell=True)
        subprocess.Popen("curl -X POST -d '{\"dst-ip\": \"10.0.0.2/32\", \"nw-proto\":\"UDP\",tp-src\":\""+port+"\",\"priority\":\"100\",\"action\":\"DENY\" }' http://localhost:8080/wm/firewall/rules/json | python -m json.tool",shell=True)
        subprocess.Popen("curl -X POST -d '{\"src-ip\": \"10.0.0.3/32\", \"nw-proto\":\"UDP\",tp-src\":\""+port+"\",\"priority\":\"100\", \"action\":\"DENY\" }' http://localhost:8080/wm/firewall/rules/json | python -m json.tool",shell=True)
        subprocess.Popen("curl -X POST -d '{\"dst-ip\": \"10.0.0.3/32\", \"nw-proto\":\"UDP\",tp-src\":\""+port+"\",\"priority\":\"100\", \"action\":\"DENY\" }' http://localhost:8080/wm/firewall/rules/json | python -m json.tool",shell=True)
    pass

# To insert the policies for the traffic applicable to path between S1 and S3
def H1toH3():
    switch_1 = '00:00:00:00:00:01'
    switch_3 = '00:00:00:00:00:03'
    flow_size = 0
    while True:
        pusher_flowS1= stat.get(switch_1)
        pusher_flowS3= stat.get(switch_3)
        if sys.getsizeof(pusher_flowS1) == sys.getsizeof(pusher_flowS3):
        # if the data flow byte size is same, that means the flow is between S1 and S3
            flow_size+= pusher_flowS1["byteCount"]
            if flow_size*8 < 20000000:
                queuecmd = "ovs-vsctl -- set port S1-eth1 other-config:max-rate=1000000"
                os.popen(queuecmd).read()
                queuecmd = "ovs-vsctl -- set port S3-eth1 other-config:max-rate=1000000"
                os.popen(queuecmd).read()
            if flow_size*8 >= 20000000 and flow_size*8 < 30000000:
                queuecmd = "ovs-vsctl -- set port S1-eth1 other-config:max-rate=512000"
                os.popen(queuecmd).read()
                queuecmd = "ovs-vsctl -- set port S3-eth1 other-config:max-rate=512000"
                os.popen(queuecmd).read()
            else:
                return

def staticForwarding():
    # Below 4 flows are for setting up the static forwarding for the path H1->S1->S2->H2 & vice-versa
    # Define static flow for Switch S1 for packet forwarding b/w h1 and h2
    S1Staticflow1 = {'switch':"00:00:00:00:00:00:00:01","name":"S1h1toh2","cookie":"0",
                    "priority":"1","in_port":"1","eth_type":"0x800","ipv4_src":"10.0.0.1",
                    "ipv4_dst":"10.0.0.2","active":"true","actions":"output=2"}
    S1Staticflow2 = {'switch':"00:00:00:00:00:00:00:01","name":"S1h2toh1","cookie":"0",
                    "priority":"1","in_port":"1","eth_type":"0x800","ipv4_src":"10.0.0.2",
                    "ipv4_dst":"10.0.0.1","active":"true","actions":"output=1"}
    # Define static flow for Switch S2 for packet forwarding b/w h1 and h2
    S2Staticflow1 = {'switch':"00:00:00:00:00:00:00:02","name":"S2h1toh2","cookie":"0",
                    "priority":"1","in_port":"1","eth_type":"0x800","ipv4_src":"10.0.0.2",
                    "ipv4_dst":"10.0.0.1","active":"true","actions":"output=2"}
    S2Staticflow2 = {'switch':"00:00:00:00:00:00:00:02","name":"S2h2toh1","cookie":"0",
                    "priority":"1","in_port":"2","eth_type":"0x800","ipv4_src":"10.0.0.1",
                    "ipv4_dst":"10.0.0.2","active":"true","actions":"output=1"}

    # Below 4 flows are for setting up the static forwarding for the path H1->S1->S3->H3 & vice-versa
    # Define static flow for Switch S1 for packet forwarding b/w h1 and h3
    S1Staticflow3 = {'switch':"00:00:00:00:00:00:00:01","name":"S1h1toh3","cookie":"0",
                    "priority":"1","in_port":"1","eth_type":"0x800","ipv4_src":"10.0.0.1",
                    "ipv4_dst":"10.0.0.3","active":"true","actions":"output=3"}
    S1Staticflow4 = {'switch':"00:00:00:00:00:00:00:01","name":"S1h3toh1","cookie":"0",
                    "priority":"1","in_port":"1","eth_type":"0x800","ipv4_src":"10.0.0.3",
                    "ipv4_dst":"10.0.0.1","active":"true","actions":"output=1"}
    # Define static flow for Switch S3 for packet forwarding b/w h1 and h3
    S3Staticflow1 = {'switch':"00:00:00:00:00:00:00:03","name":"S3h3toh1","cookie":"0",
                    "priority":"1","in_port":"1","eth_type":"0x800","ipv4_src":"10.0.0.3",
                    "ipv4_dst":"10.0.0.1","active":"true","actions":"output=2"}
    S3Staticflow2 = {'switch':"00:00:00:00:00:00:00:03","name":"S3h1toh3","cookie":"0",
                    "priority":"1","in_port":"2","eth_type":"0x800","ipv4_src":"10.0.0.1",
                    "ipv4_dst":"10.0.0.3","active":"true","actions":"output=1"}

    # Below 4 flows are for setting up the static forwarding for the path H2->S2->S3->H3 & vice-versa
    # Define static flow for Switch S1 for packet forwarding b/w h1 and h3
    S2Staticflow3 = {'switch':"00:00:00:00:00:00:00:02","name":"S2h2toh3","cookie":"0",
                    "priority":"1","in_port":"1","eth_type":"0x800","ipv4_src":"10.0.0.2",
                    "ipv4_dst":"10.0.0.3","active":"true","actions":"output=3"}
    S2Staticflow4 = {'switch':"00:00:00:00:00:00:00:02","name":"S2h3toh2","cookie":"0",
                    "priority":"1","in_port":"3","eth_type":"0x800","ipv4_src":"10.0.0.3",
                    "ipv4_dst":"10.0.0.2","active":"true","actions":"output=1"}
    # Define static flow for Switch S3 for packet forwarding b/w h1 and h3
    S3Staticflow3 = {'switch':"00:00:00:00:00:00:00:03","name":"S3h3toh2","cookie":"0",
                    "priority":"1","in_port":"1","eth_type":"0x800","ipv4_src":"10.0.0.3",
                    "ipv4_dst":"10.0.0.2","active":"true","actions":"output=3"}
    S3Staticflow4 = {'switch':"00:00:00:00:00:00:00:03","name":"S3h2toh3","cookie":"0",
                    "priority":"1","in_port":"2","eth_type":"0x800","ipv4_src":"10.0.0.2",
                    "ipv4_dst":"10.0.0.3","active":"true","actions":"output=1"}

    #Now, Insert the flows to the switches
    pusher.set(S1Staticflow1)
    pusher.set(S1Staticflow2)
    pusher.set(S1Staticflow3)
    pusher.set(S1Staticflow4)

    pusher.set(S2Staticflow1)
    pusher.set(S2Staticflow2)
    pusher.set(S2Staticflow3)
    pusher.set(S2Staticflow4)

    pusher.set(S3Staticflow1)
    pusher.set(S3Staticflow2)
    pusher.set(S3Staticflow3)
    pusher.set(S3Staticflow4)
    print "Static Forwarding is now Setup!!\n"


if __name__ =='__main__':
    # Starting the basic Forwarding/routing of packets b/w the three hosts
    staticForwarding()
    # Implementing the organizational policy, for communication path specified
    H1toH2()
    H2toH3()
    H1toH3()
    pass
