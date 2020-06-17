# bhl-item-fix
Fix BHL Item article boundaries

Using BHL and BioStor APIs to create a spreadsheet of each article in a BHL item (```php item-to-tsv.php```). Place in a Google Docs spreadsheet. Move missing plates, pages, etc. Then copy and paste sheet into a text file and run ```php tsv-to-item.php``` to generate SQL dump that we can use to update BioStor database.


## Editing spreadsheet

Template https://docs.google.com/spreadsheets/d/1gPD7mXISVAK6bOiE408uUa18mYLPSi3w8sVFnUVFO-g/edit?usp=sharing

