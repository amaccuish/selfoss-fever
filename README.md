# selfoss-fever
API bridge between Selfoss and Fever

# What works
I've only tested Reeder for iOS and macOS.

1. Syncing
2. Mark as read/unread starred/unstarred

# Installation
1. On server: Download and unzip to desired location on your server
2. On server: Edit fever/index.php with the location of your selfoss instance, and choose a username and password for the API
3. On client: Add account / URL is the folder in step 1 / username and password were chosen by you in step 2, or the default is admin@test.com, password
4. Once added, you should now be able to use your client in sync with selfoss

# Current limitations
1. Favicons are unreliable; some clients implement the favicon part of the api, and should work fine; Reeder, the client I use, decides to contact the domain specified in site_url of a feed and grab the favicon that way. Sadly this often doesn't work because the URL is a feed proxy. Selfoss could provide the URL where it found it's favicon over it's API and this could be passed to the client. It wouldn't be great to lookup endpoints ourself manually as we'd have to store them somewhere for performance.

# Technical bits
- Right now we're just querying the selfoss api and transforming requests and responses so they work for each end. This is ultimately inefficient, selfoss is having to build a large array in memory, convert it to json. We then create a socket, retrieve the JSON object, convert it back to a PHP object, make changes and then finally convert back to JSON for output. It would be better to use selfoss directly, I however didn't know how to do this in a clean way as a 'plugin' to selfoss. Also, the selfoss api already provides useful things like filtering for unread items which I'd have to reimplement, probably badly aha.
