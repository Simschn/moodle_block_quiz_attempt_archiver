#!/bin/bash
rm block.zip
cd ..
zip signed_quiz_export_block/block.zip signed_quiz_export_block/db/ signed_quiz_export_block/lang/ signed_quiz_export_block/*.php signed_quiz_export_block/composer.json -r