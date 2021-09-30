contrack
========

Contrack is a web-enabled order-centric management software that supports the planning and management of the order's complete life-cycle. It supports order entry, contacts management, production planning and tracking as well as financial monitoring.

It was created in 2005 as a bespoke system for the textile business of our friend.

# Installation
- Clone `contrack/conf/configuration.example.php` to `contrack/conf/configuration.php`
- Clone `.env.example` to `.env`
- Ensure the values in `contrack/conf/configuration.php` and `.env` are matching
- `docker-compose build && CONTRACK_WEB_PORT=8080 docker-compose up` and wait for the log to settle
- `mysql -h127.0.0.1 -uroot -pcontrack contrack < sql/contrack.sql`
- Open http://localhost:8080 and login with `admin` / `admin`
