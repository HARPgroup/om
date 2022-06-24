#!/bin/sh
# link drupal module in to this machines repo
# this is how it *should* be on the live server, but the dev server, since 
# it is a backup of live, often relies on the rsync code-base, but there is not need
rm -Rf /var/www/html/d.dh/modules/om
ln -s /opt/model/om/drupal/om /var/www/html/d.dh/modules/om


