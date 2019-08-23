import wx
import os
import time
import urllib2
import PIL
from PIL import Image
from cStringIO import StringIO

cam_ip = '192.168.0.122'
cam_ip = '192.168.0.123'


time_intival = 5

print 'Start connect the camera:'+str(cam_ip)

url_link = 'http://'+cam_ip+'/cgi-bin/video_snapshot.cgi?user=[admin]&pwd=[123456]'

while True:
    # Create the recording folder.
    time_v = time.strftime('%Y-%m-%d_%H_%M_%S')
    time_day = time.strftime('%Y-%m-%d')
    media_path = "/media/RECORD/"
    if os.path.exists(media_path+time_day):
        pass
    else:
        os.mkdir(media_path+time_day)
    # Grab image from the camera:
    print 'Grab image from the camera'
    try:
        rtnStr = urllib2.urlopen(url_link).read()
        stream = StringIO(rtnStr)
        im = Image.open(stream)
        img = wx.EmptyImage(im.size[0], im.size[1])
        img.SetData(im.convert("RGB").tostring())
        file_name = media_path+time_day+"/"+time_v+".jpg"
        print 'Save the file in the SD card.'
        img.SaveFile(file_name, wx.BITMAP_TYPE_JPEG)
    except:
        print 'have error when grab the image'

    time.sleep(10)


