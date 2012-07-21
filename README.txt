/***********************
CREDITS
Strava GPX Export Script
by Cosmo Catalano
http://cosmocatalano.com

/**********************
OVERVIEW
A PHP script that takes data from the social fitness site Strava.com and writes it as a file.
The only user-required data is a Strava activity URL, and a file format (GPX or TCX, currently).

/**********************
MORE DETAILS
The idea was to create a simple version of the script I put together to export GPX/TCX files from Strava's V1 API,
so that developers could add export capabilities to their Strava applications. 

I also wrote this with an eye toward making it easy for future developers to add new export formats. 
Just write a header and footer text file, then modify the datapoint_format(); function to specify the necessary 
transformations to the data returned from Strava's API for each point in the ride. 

Currently, the script puts as much data as it possibly can into each file. Occasionally, this has led to 
compatibility issues with GPX files, as some apps don't recognize the Temp, Cadence, and HR extensions. 

Rather than bother making an input form, I've left the user inputs in the script as $file_type and $user_url.

Don't forget to set a user-agent.  

/**********************
WORKING EXAMPLE
There is a polished version of this running at http://cosmocatalano.com/strava/export/.
It also includes few back-end modifications to get around the limitations of GoDaddy's shared hosting account.

GL;HF