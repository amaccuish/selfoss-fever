# selfoss-fever
API bridge between Selfoss and Fever

# What works
I've only tested Reeder for iOS.

1. Syncing
2. Mark as read/unread starred/unstarred

# Installation
1. On server: Download and unzip to desired location on your server
2. On server: Edit fever/index.php with the location of your selfoss instance, and choose a username and password for the API
3. On client: Add account / URL is the folder in step 1 / username and password were chosen by you in step 2, or the default is admin@test.com, password
4. Once added, you should now be able to use your client in sync with selfoss

# Current limitations
1. Favicons are unreliable; some clients implement the favicon part of the api, and should work fine; Reeder, the client I use, decides to contact the domain specified in site_url of a feed and grab the favicon that way. Sadly this often doesn't work because the URL is a feed proxy. Selfoss could provide the URL where it found it's favicon over it's API and this could be passed to the client. It wouldn't be great to lookup endpoints ourself manually as we'd have to store them somewhere for performance.
