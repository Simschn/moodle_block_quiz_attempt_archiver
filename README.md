## Block Quiz Attempt Archiver
Mit diesem Plugin lassen sich Quizversuche vom Quizersteller als PDF exportieren sowie revisionssicher durch das Time Stamping Protocol (RFC3161) im Dateisystem archiveren. Der primäre Anwendungszweck ist der Einsatz bei E-Accessments an Hochschulen.

### Abhängigkeiten
- wkhtmltopdf (Erstellt aus HTML Dateien PDFs)
- openssl (https://www.openssl.org/) (Wird benutzt um die fertigen Zip Archive durch einen externen Server zu Signieren und Validieren)

### Installation
Damit alle abhänigkeiten Richtig aufgelöst werden können, ist vor dem Einsatz das Ausführen von 
```
composer install
```
notwendig.

### Technische Informationen
Zuerst werden alle Quiz Versuche aus der Datenbank gelesen und durch Moodles internen Renderer in HTML überführt. Folgend werden durch wkhtmltopdf PDF Dateien aus dem HTML generiert. Diese werden in ein Zip Archiv gespeichert und gehashed an den Externen Timestamping Server gesendet. Dieser verifiziert den Inhalt und sendet einen signierten Hash zurück. Nachträglich besteht so die Möglichkeit die Korrektheit der Daten bis zum Zeitpunkt der Signatur zu verifizieren.

### Benutzungshinweise
Der Block kann lediglich zu mod_quiz Ressourcen hinzugefüht werden. Im Block befindet sich ein Button welcher den Archiviervorgang für das aktuelle quiz auslöst. Nach dem erfolgreichen archivieren befindet sich nun im Block ein linkt der einen Download des erstellten ZipArchives ermöglicht. Die signierten Zip Archive befinden sich folgend im moodledata/backup Ordner.

### Einstellungen 
Damit das Plugin funktioniert müssen Zwei Einstellungen gesetzt sein. Zum einen die Url des Timestamping Servers (Standard ist ein öffentlicher Server des DFN) und zum anderen ein Link zum Download des dazugehörigen Zertifikates.

### Copyright
Das plugin basiert auf (https://github.com/cbluesprl/moodle-quiz_export) welches selbst auf (https://github.com/elccHTWBerlin/moodle-quiz-export) basiert. Es wurde weitreichend verändert um als Block Instanz in Moodle verwendet werden zu können. 

## Block Quiz Attempt Archiver

This Moodle Block plugin saves and signs the results of a quiz activity timestamped with an rfc3161 compliant server. It further gives Teachers an easy way to download a Zip of all exported Results converted to PDF files. It is intended to be used as support for E-Assesments based on moodle quizzes.

### Dependencies
- wkhtmltopdf (used to render html to pdf)
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
