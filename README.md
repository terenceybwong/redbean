# RedBean performance benchmark

The benchmark is a rough test on the performance of RedBean against plain SQL statements.

To run the benchmark, create database on MySQL 5.7.
```
CREATE DATABASE redbean;
USE redbean;
CREATE USER redbean IDENTIFIED BY 'redbean';
GRANT ALL ON redbean.* to redbean;
FLUSH PRIVILEGES;
```

Run the benchmark from command line:

```bash
php -e src/benchmark-update.php
```

### Sample test result
Carried on MacBook Pro (2019)
```
Updating 100,000 records by RedBean
===> Elapsed time (prepare rows): 87.80 seconds
===> Elapsed time (commit): 0.00 seconds
===> Elapsed time (total): 87.80 seconds
Updating 100,000 records by RedBean in transaction
===> Elapsed time (prepare rows): 79.98 seconds
===> Elapsed time (commit): 0.00 seconds
===> Elapsed time (total): 79.98 seconds
Updating 100,000 records by RedBean with frozen schema
===> Elapsed time (prepare rows): 37.83 seconds
===> Elapsed time (commit): 0.00 seconds
===> Elapsed time (total): 37.83 seconds
Updating 100,000 records by RedBean in transaction with frozen schema
===> Elapsed time (prepare rows): 21.15 seconds
===> Elapsed time (commit): 0.00 seconds
===> Elapsed time (total): 21.15 seconds
Updating 100,000 records by SQL
===> Elapsed time (prepare rows): 22.53 seconds
===> Elapsed time (commit): 0.00 seconds
===> Elapsed time (total): 22.53 seconds
Updating 100,000 records by SQL in transaction
===> Elapsed time (prepare rows): 7.79 seconds
===> Elapsed time (commit): 0.00 seconds
===> Elapsed time (total): 7.79 seconds
```

Running RedBean updates in transaction is slightly faster but
generally negligible.

Frozen RedBean schema seems to have significant improvement on
performance. However, if is it only the table concerned which
is frozen, the improvement is negligible.

Updating using plain SQL is very fast, particularly when it
is run in transaction.
