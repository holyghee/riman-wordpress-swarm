#!/bin/bash

# Rückbau & Abbruch subcategories
docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Wohnungsentkernungen' --post_parent=181 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[11]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Teilabbruch' --post_parent=181 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[12]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Komplettabbruch' --post_parent=181 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[13]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Industrierückbau' --post_parent=181 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[14]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Gebäuderückbau' --post_parent=181 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[15]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

# Altlastensanierung subcategories  
docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Bodensanierung' --post_parent=182 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[16]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Grundwassersanierung' --post_parent=182 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[17]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Tankstellensanierung' --post_parent=182 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[18]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Deponiesanierung' --post_parent=182 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[19]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

# Schadstoffsanierung subcategories
docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Asbestsanierung' --post_parent=183 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[20]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='PCB-Sanierung' --post_parent=183 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[21]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='PAK-Sanierung' --post_parent=183 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[22]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Schimmelsanierung' --post_parent=183 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[23]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='KMF-Sanierung' --post_parent=183 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[24]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

# Arbeitssicherheit subcategories
docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Sicherheitskoordination' --post_parent=184 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[25]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Gefährdungsbeurteilung' --post_parent=184 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[26]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Arbeitsschutzbetreuung' --post_parent=184 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[27]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Notfallmanagement' --post_parent=184 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[28]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

# Beratung & Mediation subcategories
docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Bauberatung' --post_parent=185 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[29]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Umweltberatung' --post_parent=185 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[30]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

docker exec riman-wordpress-swarm-wpcli-1 wp post create --post_type=page --post_status=publish --post_title='Konfliktmediation' --post_parent=185 --post_content='<!-- wp:query {"queryId":0,"query":{"perPage":10,"categories":[31]}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title {"isLink":true} /--><!-- wp:post-excerpt /--><!-- /wp:post-template --></div><!-- /wp:query -->' --porcelain

echo "All subcategory pages created successfully!"
