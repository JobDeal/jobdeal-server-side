# Jobdeal
Development information

## Tech stack
The application is built with (at the time of writing) Laravel 5.7, running on PHP 7.2, Apache 2, and MySQL 5.7.

### Issues
- composer.lock in this repository has been run on PHP 7.3 so will not work with 7.2. This will have to be corrected before a decent deployment tool is implemented.
- There seem to be more discrepancies between the code that's live and what we have in the repository.
- Laravel 5.7 reached End-Of-Life (EOL) 4 September 2019 so needs to be updated.
- PHP 7.2 reached EOL at the end of 2020 so should be updated.
- **The code is currently owned by root on the server.**

## Deployment
The application is deployed by uploading individual files to the *prod* or *stage* environments on the server via SFTP. There's no other method in use at the moment. 

## Testing
### Build test environment
There's a docker setup that can be used to run tests, run `make build_test` to start the docker environment. You 
can then jump into the container with `docker exec -it --user jobdeal jobdeal /bin/bash`.

### Run tests
Tests can be run with either `vendor/bin/phpunit` from within the container or you can run the full test suite with `make test`. The docker test environment must be built and run before running with make. See [Build test environment](#Build test environment).
 
### Database seeds
Some data already exists for seeding. All for the one real test but some of it should be possible to reuse.
