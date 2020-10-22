# OpenCloud

[install-php]: https://www.php.net/manual/en/features.file-upload.php#107406
[install-ms]: #link
[install-ios]: #link
[install-android]: #link
[wiki]: https://ilosey14.github.io/wiki/open-cloud

⛅ *Roll your own cloud storage.*

**OpenCloud** aims to be a plug-and-play cloud storage api for your own server space.
The goal is to provide your preferred backend environment with a headless
content management system (CMS) API and one-off session authentication.

Given a compatible frontend, start by authorizing one-time login access on your server.
An authorized session generates login tokens and an optional qr code.
Frontend login flow should prompt these values before passing them to the api,
return valid access tokens on authentication.

## Generating a login session

Generate a one-time login session.
The following registers this to the access control list (ACL) and outputs session info for immediate use.
The ACL session is immediately deleted after successful login.

```bash
sudo bash login
```

---

## Installation

<!--
### Client (Desktop/Mobile)

[💼 Microsoft Store][install-ms] \
[🍎 App Store][install-ios] \
[▶ Play Store][install-android]
-->

### ◻ Apache 2 / PHP 7.X

Configure the `php.ini` for file uploads.

[PHP.net Reference][install-php]

```ini
file_uploads = on
session.gc_maxlifetime = 10800
max_input_time = 10800
max_execution_time = 10800
upload_max_filesize = 110M
post_max_size = 120M
```

<!-- *other languages on apache...* -->


### ◻ Node.js

TODO...

### ◻ Other Backends

TODO...

---

## API

[Wiki: TODO][wiki]

## TODO

- [x] CRUD API
- [x] Database API
- [x] Authentication
- [x] Login session generation
- [ ] Wiki
  - Install/plug-in-play guide
  - Recommendations/config
- [ ] Recycling Bin for deleted items