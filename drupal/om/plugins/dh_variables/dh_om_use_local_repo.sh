#!/bin/sh

# make the main module on dev server use local repository
rm /var/www/html/d.dh/modules/om -Rf
ln -s /opt/model/om/drupal/om /var/www/html/d.dh/modules/om
