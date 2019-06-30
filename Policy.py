#!/usr/bin/python

"""
@Author <LiuYuancheng/A>
Date :
"""


import httplib
import json
import os
import subprocess

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
    subprocess.Popen( "ovs-vsctl add-br s1",shell=True)
    subprocess.Popen( "ovs-vsctl add-port s eth0", shell=True)
    subprocess.Popen("ovs-vsctl set port eth1 qos=@newqos -- --id=@newqos create qos \
                    type=linux-htb other-config:max-rate=5000000 queues:0=@newqueue -- \
                    --id=@newqueue create queue other-config:min-rate=1000000 \
                    other-config:max-rate=1000000", shell=True)
    # Set the speed limitation on S1
    subprocess.Popen( "ovs-vsctl add-br s2",shell=True)
    subprocess.Popen( "ovs-vsctl add-port s2 eth0", shell=True)
    subprocess.Popen("ovs-vsctl set port eth1 qos=@newqos -- --id=@newqos create qos \
                    type=linux-htb other-config:max-rate=5000000 queues:0=@newqueue -- \
                    --id=@newqueue create queue other-config:min-rate=1000000 \
                    other-config:max-rate=1000000", shell=True)

#    subprocess.Popen( "sudo ovs-vsctl %s -- --id=@defaultqos create qos type=linux-htb other-config:max-rate=1000000000 queues=0=@q0,1=@q1,2=@q2 -- --id=@q0 create queue other-config:max-rate=1000000000  other-config:min-rate=1000000000 -- --id=@q1 create queue other-config:max-rate=1000000 other-config:min-rate=1000000 -- --id=@q2 create queue other-config:max-rate=512000 other-config:min-rate=512000",shell=True)

    pass


# To insert the policies for the traffic applicable to path between S2 and S3
def H2toH3():
    for i in range(1000,1100):
        port = str(i)
        S2toS3UDPBlock = {'src-ip': "10.0.0.2/32", "dst-ip": "10.0.0.3", "nw-proto": "UDP","tp-src": port, "priority": "10", "action": "DENY"}
        S3toS2UDPBlock = {'src-ip': "10.0.0.3/32", "dst-ip": "10.0.0.2", "nw-proto": "UDP","tp-src": port, "priority": "10", "action": "DENY"}
        pusher.set(S2toS3UDPBlock)
        pusher.set(S2toS3UDPBlock)
    # block all UDP from 1000 to 1100




    # allow UDP communication
    #subprocess.Popen("curl -X POST -d '{\"src-ip\": \"10.0.0.2/32\", \"dl-type\":\"ARP\" }' http://localhost:8080/wm/firewall/rules/json",shell=True)
    #subprocess.Popen("curl -X POST -d '{\"dst-ip\": \"10.0.0.2/32\", \"dl-type\":\"ARP\" }' http://localhost:8080/wm/firewall/rules/json",shell=True)
    #subprocess.Popen("curl -X POST -d '{\"src-ip\": \"10.0.0.3/32\", \"dl-type\":\"ARP\" }' http://localhost:8080/wm/firewall/rules/json",shell=True)
    #subprocess.Popen("curl -X POST -d '{\"dst-ip\": \"10.0.0.3/32\", \"dl-type\":\"ARP\" }' http://localhost:8080/wm/firewall/rules/json",shell=True)

    #subprocess.Popen("curl -X POST -d '{\"src-ip\": \"10.0.0.2/32\", \"nw-proto\":\"UDP\" }' http://localhost:8080/wm/firewall/rules/json",shell=True)
    #subprocess.Popen("curl -X POST -d '{\"dst-ip\": \"10.0.0.2/32\", \"nw-proto\":\"UDP\" }' http://localhost:8080/wm/firewall/rules/json",shell=True)
    #subprocess.Popen("curl -X POST -d '{\"src-ip\": \"10.0.0.3/32\", \"nw-proto\":\"UDP\" }' http://localhost:8080/wm/firewall/rules/json",shell=True)
    #subprocess.Popen("curl -X POST -d '{\"dst-ip\": \"10.0.0.3/32\", \"nw-proto\":\"UDP\" }' http://localhost:8080/wm/firewall/rules/json",shell=True)
    # block port 1000 to 1100
    #for i in range(1000, 1100):
    #    subprocess.Popen("curl -X POST -d '{\"src-ip\": \"10.0.0.2/32\", \"nw-proto\":\"UDP\",tp-src\":\""+str(i)+"\",\"priority\":\"100\", \"action\":\"DENY\" }' http://localhost:8080/wm/firewall/rules/json | python -m json.tool",shell=True)
    #    subprocess.Popen("curl -X POST -d '{\"dst-ip\": \"10.0.0.2/32\", \"nw-proto\":\"UDP\",tp-src\":\""+str(i)+"\",\"priority\":\"100\",\"action\":\"DENY\" }' http://localhost:8080/wm/firewall/rules/json | python -m json.tool",shell=True)
    #    subprocess.Popen("curl -X POST -d '{\"src-ip\": \"10.0.0.3/32\", \"nw-proto\":\"UDP\",tp-src\":\""+str(i)+"\",\"priority\":\"100\", \"action\":\"DENY\" }' http://localhost:8080/wm/firewall/rules/json | python -m json.tool",shell=True)
    #    subprocess.Popen("curl -X POST -d '{\"dst-ip\": \"10.0.0.3/32\", \"nw-proto\":\"UDP\",tp-src\":\""+str(i)+"\",\"priority\":\"100\", \"action\":\"DENY\" }' http://localhost:8080/wm/firewall/rules/json | python -m json.tool",shell=True)
    pass
# To insert the policies for the traffic applicable to path between S1 and S3

def find_all(a_str, sub_str):
    start = 0
    b_starts = []
    while True:
        start = a_str.find(sub_str, start)
        if start == -1: return b_starts
        #print start
        b_starts.append(start)
        start += 1

def H1toH3():
    cmd = "ovs-vsctl show"
    p = os.popen(cmd).read()
    # print p
    brdgs = find_all(p, "Bridge")

    switches = []
    for bn in brdgs:
        sw = p[(bn + 8):(bn + 10)]
        switches.append(sw)

    ports = find_all(p, "Port")
    # print ports
    prts = []
    for prt in ports:
        prt = p[(prt + 6):(prt + 13)]
        if '"' not in prt:
            # print prt
            prts.append(prt)
    config_strings = {}
    for i in range(len(switches)):
        str = ""
        sw = switches[i]
        for n in range(len(prts)):
            # verify correct order
            if switches[i] in prts[n]:
                # print switches[i]
                # print prts[n]
                port_name = prts[n]
                str = str + " -- set port %s qos=@defaultqos" % port_name
        config_strings[sw] = str
    flow_size = 0
    print "-------------------"
    while True:
        pusher_flowS1= stat.get("S1")
        pusher_flowS3= stat.get('S3')
	print len(pusher_flowS1)
	print len(pusher_flowS3)

        if len(pusher_flowS1) == len(pusher_flowS3):
        # if the data len is same, that means the flow is between S1 and S3
            flow_size+= len(pusher_flowS1)
            #if flow_size*8 < 20000000:
             #   queuecmd = "sudo ovs-vsctl %s -- --id=@defaultqos create qos type=linux-htb other-config:max-rate=1000000000 queues=0=@q0,1=@q1,-- --id=@q0 create queue other-config:max-rate=1000000000  other-config:min-rate=1000000000 -- --id=@q1 create queue other-config:max-rate=1000000 other-config:min-rate=1000000" % config_strings['S1']
              #  os.popen(queuecmd).read()
              #  queuecmd = "sudo ovs-vsctl %s -- --id=@defaultqos create qos type=linux-htb other-config:max-rate=1000000000 queues=0=@q0,1=@q1, -- --id=@q0 create queue other-config:max-rate=1000000000  other-config:min-rate=1000000000 -- --id=@q1 create queue other-config:max-rate=1000000 other-config:min-rate=1000000" % config_strings['S3']
               # os.popen(queuecmd).read()
            #if flow_size*8 >= 20000000 and flow_size*8 < 30000000:
            #    queuecmd = "sudo ovs-vsctl %s -- --id=@defaultqos create qos type=linux-htb other-config:max-rate=1000000000 queues=0=@q0,2=@q2 -- --id=@q0 create queue other-config:max-rate=1000000000  other-config:min-rate=1000000000  -- --id=@q2 create queue other-config:max-rate=512000 other-config:min-rate=512000" % config_strings['S1']
            #    os.popen(queuecmd).read()
             #   queuecmd = "sudo ovs-vsctl %s -- --id=@defaultqos create qos type=linux-htb other-config:max-rate=1000000000 queues=0=@q0,2=@q2 -- --id=@q0 create queue other-config:max-rate=1000000000  other-config:min-rate=1000000000  -- --id=@q2 create queue other-config:max-rate=512000 other-config:min-rate=512000" % config_strings['S3']
              #  os.popen(queuecmd).read()

    pass


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
    #H1toH2()
    #H2toH3()
    H1toH3()
    pass
