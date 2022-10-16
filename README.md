# Piwigo Metamore
Get more out of your metadata

* Internal name: `LocalFilesEditor` (directory name in `plugins/`)
* Plugin page: https://github.com/geekitude/piwigo-plugin-metamore
* Translation: ???

This plugin adds two blocks of metadata below picture description : one just adds `event` IPTC while the other show a mix of EXIF/IPTC with pictograms (idea from [Bootstrap Darkmoon](https://piwigo.org/ext/extension_view.php?eid=831) theme) including links to corresponding permalinked [Smart Albums](https://piwigo.org/ext/extension_view.php?eid=544).

Sadly, all this is very specific to each installation and requires a lot of work compared to simply installing a plugin. The code will most likely never reach a point where it can be officially published as a plugin.

## First step : collect data and store to pictures

That step consists in preparing images to include desired data. This includes :
* Focal Length in 35mm Format : we use both an APS-C and Micro Four Thirds systems so this is very important for our gallery and that data is most often ignored by camera makers
* Lens model is important when in need to choose to keep or ditch a lens. While most camera makers try to propose that data but the field storing it is not universal and manual lenses are also a problem.

Photo Mechanic software works wonders to collect those data (I belive it knows where to get the data depending on manufacturer and how to calculate missing 35mm focal length). The trick is to use it's variable system to store {lenstype} and {lens35} values in IPTC fields you know you will never use, in my case `Credit` (#110) and `Source` (#115).

## Second step : transfer data from pictures to database

### Create fields in database

Being abble to use data (like in [Smart Albums](https://piwigo.org/ext/extension_view.php?eid=544)) instead of just displaying it means you need to store it in database so the first thing to do is to add fields to `images` table of Piwigo's database.

I added :
* event (varchar 255)
* make (tinytext)
* model (tinytext)
* lenstype (varchar 50)
* lens35 (smallint 4)
