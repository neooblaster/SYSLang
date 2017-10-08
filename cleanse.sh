#!/bin/bash
# Nettoyage du dossier coverage
cd coverage
ls -a | egrep -v "^\.+$|.required" | xargs -r rm -r
cd -

# Nettoyage du dossier docs
cd docs
ls -a | egrep -v "^\.+$|README.md|cache" | xargs -r rm -r
cd cache
ls -a | egrep -v "^\.+$|.required" | xargs -r rm -r
