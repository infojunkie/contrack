contrack
========

Contrack is a web-enabled order-centric management software that supports the planning and management of the order's complete life-cycle. It supports order entry, contacts management, production planning and tracking as well as financial monitoring.

It was created in 2005 as a bespoke system for the textile business of our friend.

# Installation
- Clone `.env.example` to `.env`
- `docker-compose build && CONTRACK_WEB_PORT=8080 docker-compose up` and wait for the log to settle
- `docker-compose exec -T db mysql -pcontrack contrack < sql/contrack.sql` to initialize the database
- Open http://localhost:8080 and login with `admin` / `admin`
