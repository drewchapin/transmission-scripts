#!/bin/bash
RSS_URL="http://showrss.info/user/53066.rss?magnets=false&namespaces=false&name=null&quality=null&re=null"

curl $RSS_URL | grep -oh '<link>[^<]*</link>' | sed -E 's/<\/?link>//g'
