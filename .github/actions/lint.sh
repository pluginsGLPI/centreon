#!/bin/bash
set -e -u -x -o pipefail

docker exec --user www-data glpi /bin/bash -c "(cd /var/www/glpi/plugins/centreon && ./vendor/bin/parallel-lint --colors --exclude ./vendor/ .)"
docker exec --user www-data glpi /bin/bash -c "(cd /var/www/glpi/plugins/centreon && ./vendor/bin/phpcs)"