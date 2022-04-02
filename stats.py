# SPDX-FileCopyrightText: 2021 ladyada for Adafruit Industries
# SPDX-License-Identifier: MIT

# -*- coding: utf-8 -*-

import time
import subprocess
import digitalio
import board
import busio
from PIL import Image, ImageDraw, ImageFont
from adafruit_rgb_display import st7789
import mariadb
from datetime import datetime
import adafruit_veml7700

# allow mariadb a chance to start before connecting
time.sleep(10)

mydb = mariadb.connect(
    host="localhost",
    user="root",
    password="password",
    database="greenhouse"
)
print(mydb)

# Get mariadb cursor
cur = mydb.cursor()

# Configuration for CS and DC pins (these are FeatherWing defaults on M0/M4):
cs_pin = digitalio.DigitalInOut(board.CE0)
dc_pin = digitalio.DigitalInOut(board.D25)
reset_pin = None

# Config for display baudrate (default max is 24mhz):
BAUDRATE = 64000000

# Setup SPI bus using hardware SPI:
spi = board.SPI()

# Create the ST7789 display:
disp = st7789.ST7789(
    spi,
    cs=cs_pin,
    dc=dc_pin,
    rst=reset_pin,
    baudrate=BAUDRATE,
    width=240,
    height=240,
    x_offset=0,
    y_offset=80,
)

i2c = busio.I2C(board.SCL, board.SDA)
veml7700 = adafruit_veml7700.VEML7700(i2c)

# Create blank image for drawing.
# Make sure to create image with mode 'RGB' for full color.
height = disp.width  # we swap height/width to rotate it to landscape!
width = disp.height
image = Image.new("RGB", (width, height))
rotation = 180

# Get drawing object to draw on image.
draw = ImageDraw.Draw(image)

# Draw a black filled box to clear the image.
draw.rectangle((0, 0, width, height), outline=0, fill=(0, 0, 0))
disp.image(image, rotation)
# Draw some shapes.
# First define some constants to allow easy resizing of shapes.
padding = -2
top = padding
bottom = height - padding
# Move left to right keeping track of the current x position for drawing shapes.
x = 0


# Alternatively load a TTF font.  Make sure the .ttf font file is in the
# same directory as the python script!
# Some other nice fonts to try: http://www.dafont.com/bitmap.php
font1 = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 20)
font2 = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 14)
fontMono20 = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf", 20)

# Turn on the backlight
backlight = digitalio.DigitalInOut(board.D22)
backlight.switch_to_output()
backlight.value = True

icon = Image.open("plant48.png")

lasttime = time.time()

while True:
    #print("Ambient light:", veml7700.light)
    #print("Lux:", veml7700.lux)

    if (time.time() - lasttime) > 30:
        sql = ("insert into temps (sensor_id, temp_c) values (6, " + str(veml7700.lux) + ")")
        cur.execute(sql)
        mydb.commit()
        print(cur.rowcount, "record inserted.")
        lasttime = time.time()

    # Draw a black filled box to clear the image.
    draw.rectangle((0, 0, width, height), outline=0, fill=0)

    # Display image.
    image.paste(icon, (180,0))
    
    y = top

    now = datetime.now()
    s = now.strftime("%-I:%M:%S %p")
    draw.text((x, y), s, font=font1, fill="#FFFFFF")
    y += font1.getsize(s)[1]
    s = now.strftime("%a %b %-d, %Y")
    draw.text((x, y), s, font=font2, fill="#FFFFFF")
    y += font2.getsize(s)[1]
    
    y+= 30
    
    mydb.commit()
    #cur.execute("SELECT temp_c, temp_time, sensor_name FROM `temps` a left join sensors b on a.sensor_id = b.sensor_id where temp_time > date_add(now(), interval -1 day) order by temp_time desc limit 5")
    cur.execute("select sensor_name, recent_temp from sensors")
    for (sensor_name, recent_temp) in cur:
        temp_f = (recent_temp * (9/5)) + 32
        s = "{:<13}{:.1f}\N{DEGREE SIGN}F".format(sensor_name, temp_f)
        print(s)
        draw.text((x, y), s, font=fontMono20, fill="#FFFFFF")
        y += fontMono20.getsize(s)[1]
        
    cmd = "top -bn1 | grep load | awk '{printf \"CPU Load: %.2f\", $(NF-2)}'"
    CPU = subprocess.check_output(cmd, shell=True).decode("utf-8")
    
    cmd = "free -m | awk 'NR==2{printf \"Mem: %s/%s MB  %.2f%%\", $3,$2,$3*100/$2 }'"
    MemUsage = subprocess.check_output(cmd, shell=True).decode("utf-8")

    cmd = "cat /sys/class/thermal/thermal_zone0/temp |  awk '{printf \"CPU Temp: %.1f\N{DEGREE SIGN}C\", $(NF-0) / 1000}'"  # pylint: disable=line-too-long
    Temp = subprocess.check_output(cmd, shell=True).decode("utf-8")

    # Write four lines of text.
    y += 20
    draw.text((x, y), CPU, font=font2, fill="#FFFFFF")
    y += font2.getsize(CPU)[1]
    draw.text((x, y), MemUsage, font=font2, fill="#FFFFFF")
    y += font2.getsize(MemUsage)[1]
    draw.text((x, y), Temp, font=font2, fill="#FFFFFF")

    # Display image.
    disp.image(image, rotation)
    time.sleep(2)

