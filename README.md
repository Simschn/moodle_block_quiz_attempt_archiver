## Block Revision Safe Quiz result exporting

This Moodle Block plugin saves and signs the results of a quiz activity timestamped with an rfc3161 compliant server. It further gives Teachers an easy way to download a Zip of all exported Results converted to PDF files. It is intended to be used as support for E-Assesments based on moodle quizzes.

### Dependencies
- mpdf (https://mpdf.github.io/)
- openssl (https://www.openssl.org/) must be installed independently based on OS Distribution. It is needed for creating and verifying Timestamping requests and responses.

### Installation
This repository does not contain a vendor folder by itself. Before deploying please use 'composer install' to download dependencies.

### Technical Information
First result data is gathered from the Database. Then it is rendered to an html file using Moodles internal Quiz renderer to resemble the Applications Display of results. Further these PDFs are hashed and signed by an rfc3161 compliant Server. All the files are saved in Moodledatas backups folder with a naming convention of 'year/coursename/quizname'

### Practical Information
This Plugin can only be added to Quiz Activity instances. It will then Display a 'Sign and Save' Button. Upon finishing of a Student Quiz, the teacher is to press this Button to initiate the Signing and Saving mechanism. For now there Plugin does not give immediate feedback on the progress. Depending on the amount of Questions / Students the pdf rendering process can take up to a few Minutes. Once the rendering is done, a link will appear in the Block which lets the Teacher download all results as a Zip file.

### Settings
For the Plugin to work there are two fields to be set. The first is the Url of a valid rfc3161 compliant Server and a Url to the Certificate Chain needed to verify a timestamping response file.

### Copyright
This plugin is based on (https://github.com/cbluesprl/moodle-quiz_export) which itself is
based on https://github.com/elccHTWBerlin/moodle-quiz-export. It has been modified into the form of a Block Plugin
and uses an added timestamping mechanism for revision safe storing.