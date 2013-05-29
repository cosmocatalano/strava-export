/***********************
CREDITS
Strava Export Script - EXODUS EDITION
by Cosmo Catalano
http://cosmocatalano.com

/**********************
OVERVIEW
This is a PHP script that takes ALL YOUR RIDES from the social fitness site Strava.com and saves them as files.
The only user-required data is a Strava activity URL, and a file format (GPX or TCX, currently).

Strava is CHANGING THEIR API VERY SOON (within days).

This will make downloading rides impossible, or at the very least awkward.

Maybe they'll change this, maybe they won't. Best to get your data now and be safe about it.

/**********************
THINGS TO KNOW
This is designed to run from the command line, and will save your files as GPX or TCX to your home (~) directory. On OS X, it's the folder that looks like a house.
Keep in mind that this may take many gigabytes of space if you have a lot of rides. 
Rides will be saved as their strava ID. Not descriptive, but the best solution given the constraints :(

ON A MAC/LINUX
(this assumes you have PHP installed)
 -  download the ZIP from Github. Unzip it. Put it somwhere
 -  Open Terminal, type 'php', then the path to wherever the unzipped version is
 	e.g., the full command would be 'php ~/Downloads/strava-export-exodus/exodus.php' 
 	if you saved it in your Downloads folder (on OS X). 
 -  Follow the instructions. The script will try and keep you updated.
 
ON A PC
- uh...probably Google "command line php windows"? Honestly, I don't know & don't have time.
 
I made this in a hurry. Not sure what'll happen with trainer rides, skiing/walking events, etc. 

/**********************
