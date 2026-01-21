[comment]: <> (# Faker)

[comment]: <> ([![Monthly Downloads]&#40;https://poser.pugx.org/fzaninotto/faker/d/monthly.png&#41;]&#40;https://packagist.org/packages/fzaninotto/faker&#41;)

[comment]: <> ([![Continuous Integration]&#40;https://github.com/fzaninotto/Faker/workflows/Continuous%20Integration/badge.svg?branch=master&#41;]&#40;https://github.com/fzaninotto/Faker/actions&#41;)

[comment]: <> ([![codecov]&#40;https://codecov.io/gh/fzaninotto/Faker/branch/master/graph/badge.svg&#41;]&#40;https://codecov.io/gh/fzaninotto/Faker&#41;)

[comment]: <> ([![SensioLabsInsight]&#40;https://insight.sensiolabs.com/projects/eceb78a9-38d4-4ad5-8b6b-b52f323e3549/mini.png&#41;]&#40;https://insight.sensiolabs.com/projects/eceb78a9-38d4-4ad5-8b6b-b52f323e3549&#41;)

[comment]: <> (Faker is a PHP library that generates fake data for you. Whether you need to bootstrap your database, create good-looking XML documents, fill-in your persistence to stress test it, or anonymize data taken from a production service, Faker is for you.)

[comment]: <> (Faker is heavily inspired by Perl's [Data::Faker]&#40;http://search.cpan.org/~jasonk/Data-Faker-0.07/&#41;, and by ruby's [Faker]&#40;https://rubygems.org/gems/faker&#41;.)

[comment]: <> (Faker requires PHP >= 5.3.3.)

[comment]: <> (**Faker is archived**. Read the reasons behind this decision here: [https://marmelab.com/blog/2020/10/21/sunsetting-faker.html]&#40;https://marmelab.com/blog/2020/10/21/sunsetting-faker.html&#41; )

[comment]: <> (# Table of Contents)

[comment]: <> (- [Installation]&#40;#installation&#41;)

[comment]: <> (- [Basic Usage]&#40;#basic-usage&#41;)

[comment]: <> (- [Formatters]&#40;#formatters&#41;)

[comment]: <> (	- [Base]&#40;#fakerproviderbase&#41;)

[comment]: <> (	- [Lorem Ipsum Text]&#40;#fakerproviderlorem&#41;)

[comment]: <> (	- [Person]&#40;#fakerprovideren_usperson&#41;)

[comment]: <> (	- [Address]&#40;#fakerprovideren_usaddress&#41;)

[comment]: <> (	- [Phone Number]&#40;#fakerprovideren_usphonenumber&#41;)

[comment]: <> (	- [Company]&#40;#fakerprovideren_uscompany&#41;)

[comment]: <> (	- [Real Text]&#40;#fakerprovideren_ustext&#41;)

[comment]: <> (	- [Date and Time]&#40;#fakerproviderdatetime&#41;)

[comment]: <> (	- [Internet]&#40;#fakerproviderinternet&#41;)

[comment]: <> (	- [User Agent]&#40;#fakerprovideruseragent&#41;)

[comment]: <> (	- [Payment]&#40;#fakerproviderpayment&#41;)

[comment]: <> (	- [Color]&#40;#fakerprovidercolor&#41;)

[comment]: <> (	- [File]&#40;#fakerproviderfile&#41;)

[comment]: <> (	- [Image]&#40;#fakerproviderimage&#41;)

[comment]: <> (	- [Uuid]&#40;#fakerprovideruuid&#41;)

[comment]: <> (	- [Barcode]&#40;#fakerproviderbarcode&#41;)

[comment]: <> (	- [Miscellaneous]&#40;#fakerprovidermiscellaneous&#41;)

[comment]: <> (	- [Biased]&#40;#fakerproviderbiased&#41;)

[comment]: <> (	- [Html Lorem]&#40;#fakerproviderhtmllorem&#41;)

[comment]: <> (- [Modifiers]&#40;#modifiers&#41;)

[comment]: <> (- [Localization]&#40;#localization&#41;)

[comment]: <> (- [Populating Entities Using an ORM or an ODM]&#40;#populating-entities-using-an-orm-or-an-odm&#41;)

[comment]: <> (- [Seeding the Generator]&#40;#seeding-the-generator&#41;)

[comment]: <> (- [Faker Internals: Understanding Providers]&#40;#faker-internals-understanding-providers&#41;)

[comment]: <> (- [Real Life Usage]&#40;#real-life-usage&#41;)

[comment]: <> (- [Language specific formatters]&#40;#language-specific-formatters&#41;)

[comment]: <> (- [Third-Party Libraries Extending/Based On Faker]&#40;#third-party-libraries-extendingbased-on-faker&#41;)

[comment]: <> (- [License]&#40;#license&#41;)


[comment]: <> (## Installation)

[comment]: <> (```sh)

[comment]: <> (composer require fzaninotto/faker)

[comment]: <> (```)

[comment]: <> (## Basic Usage)

[comment]: <> (### Autoloading)

[comment]: <> (Faker supports both `PSR-0` as `PSR-4` autoloaders.)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (# When installed via composer)

[comment]: <> (require_once 'vendor/autoload.php';)

[comment]: <> (```)

[comment]: <> (You can also load `Fakers` shipped `PSR-0` autoloader)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (# Load Fakers own autoloader)

[comment]: <> (require_once '/path/to/Faker/src/autoload.php';)

[comment]: <> (```)

[comment]: <> (*alternatively, you can use any another PSR-4 compliant autoloader*)

[comment]: <> (### Create fake data)

[comment]: <> (Use `Faker\Factory::create&#40;&#41;` to create and initialize a faker generator, which can generate data by accessing properties named after the type of data you want.)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// use the factory to create a Faker\Generator instance)

[comment]: <> ($faker = Faker\Factory::create&#40;&#41;;)

[comment]: <> (// generate data by accessing properties)

[comment]: <> (echo $faker->name;)

[comment]: <> (  // 'Lucy Cechtelar';)

[comment]: <> (echo $faker->address;)

[comment]: <> (  // "426 Jordy Lodge)

[comment]: <> (  // Cartwrightshire, SC 88120-6700")

[comment]: <> (echo $faker->text;)

[comment]: <> (  // Dolores sit sint laboriosam dolorem culpa et autem. Beatae nam sunt fugit)

[comment]: <> (  // et sit et mollitia sed.)

[comment]: <> (  // Fuga deserunt tempora facere magni omnis. Omnis quia temporibus laudantium)

[comment]: <> (  // sit minima sint.)

[comment]: <> (```)

[comment]: <> (Even if this example shows a property access, each call to `$faker->name` yields a different &#40;random&#41; result. This is because Faker uses `__get&#40;&#41;` magic, and forwards `Faker\Generator->$property` calls to `Faker\Generator->format&#40;$property&#41;`.)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (for &#40;$i = 0; $i < 10; $i++&#41; {)

[comment]: <> (  echo $faker->name, "\n";)

[comment]: <> (})

[comment]: <> (  // Adaline Reichel)

[comment]: <> (  // Dr. Santa Prosacco DVM)

[comment]: <> (  // Noemy Vandervort V)

[comment]: <> (  // Lexi O'Conner)

[comment]: <> (  // Gracie Weber)

[comment]: <> (  // Roscoe Johns)

[comment]: <> (  // Emmett Lebsack)

[comment]: <> (  // Keegan Thiel)

[comment]: <> (  // Wellington Koelpin II)

[comment]: <> (  // Ms. Karley Kiehn V)

[comment]: <> (```)

[comment]: <> (**Tip**: For a quick generation of fake data, you can also use Faker as a command line tool thanks to [faker-cli]&#40;https://github.com/bit3/faker-cli&#41;.)

[comment]: <> (## Formatters)

[comment]: <> (Each of the generator properties &#40;like `name`, `address`, and `lorem`&#41; are called "formatters". A faker generator has many of them, packaged in "providers". Here is a list of the bundled formatters in the default locale.)

[comment]: <> (### `Faker\Provider\Base`)

[comment]: <> (    randomDigit             // 7)

[comment]: <> (    randomDigitNot&#40;5&#41;       // 0, 1, 2, 3, 4, 6, 7, 8, or 9)

[comment]: <> (    randomDigitNotNull      // 5)

[comment]: <> (    randomNumber&#40;$nbDigits = NULL, $strict = false&#41; // 79907610)

[comment]: <> (    randomFloat&#40;$nbMaxDecimals = NULL, $min = 0, $max = NULL&#41; // 48.8932)

[comment]: <> (    numberBetween&#40;$min = 1000, $max = 9000&#41; // 8567)

[comment]: <> (    randomLetter            // 'b')

[comment]: <> (    // returns randomly ordered subsequence of a provided array)

[comment]: <> (    randomElements&#40;$array = array &#40;'a','b','c'&#41;, $count = 1&#41; // array&#40;'c'&#41;)

[comment]: <> (    randomElement&#40;$array = array &#40;'a','b','c'&#41;&#41; // 'b')

[comment]: <> (    shuffle&#40;'hello, world'&#41; // 'rlo,h eoldlw')

[comment]: <> (    shuffle&#40;array&#40;1, 2, 3&#41;&#41; // array&#40;2, 1, 3&#41;)

[comment]: <> (    numerify&#40;'Hello ###'&#41; // 'Hello 609')

[comment]: <> (    lexify&#40;'Hello ???'&#41; // 'Hello wgt')

[comment]: <> (    bothify&#40;'Hello ##??'&#41; // 'Hello 42jz')

[comment]: <> (    asciify&#40;'Hello ***'&#41; // 'Hello R6+')

[comment]: <> (    regexify&#40;'[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}'&#41;; // sm0@y8k96a.ej)

[comment]: <> (### `Faker\Provider\Lorem`)

[comment]: <> (    word                                             // 'aut')

[comment]: <> (    words&#40;$nb = 3, $asText = false&#41;                  // array&#40;'porro', 'sed', 'magni'&#41;)

[comment]: <> (    sentence&#40;$nbWords = 6, $variableNbWords = true&#41;  // 'Sit vitae voluptas sint non voluptates.')

[comment]: <> (    sentences&#40;$nb = 3, $asText = false&#41;              // array&#40;'Optio quos qui illo error.', 'Laborum vero a officia id corporis.', 'Saepe provident esse hic eligendi.'&#41;)

[comment]: <> (    paragraph&#40;$nbSentences = 3, $variableNbSentences = true&#41; // 'Ut ab voluptas sed a nam. Sint autem inventore aut officia aut aut blanditiis. Ducimus eos odit amet et est ut eum.')

[comment]: <> (    paragraphs&#40;$nb = 3, $asText = false&#41;             // array&#40;'Quidem ut sunt et quidem est accusamus aut. Fuga est placeat rerum ut. Enim ex eveniet facere sunt.', 'Aut nam et eum architecto fugit repellendus illo. Qui ex esse veritatis.', 'Possimus omnis aut incidunt sunt. Asperiores incidunt iure sequi cum culpa rem. Rerum exercitationem est rem.'&#41;)

[comment]: <> (    text&#40;$maxNbChars = 200&#41;                          // 'Fuga totam reiciendis qui architecto fugiat nemo. Consequatur recusandae qui cupiditate eos quod.')

[comment]: <> (### `Faker\Provider\en_US\Person`)

[comment]: <> (    title&#40;$gender = null|'male'|'female'&#41;     // 'Ms.')

[comment]: <> (    titleMale                                 // 'Mr.')

[comment]: <> (    titleFemale                               // 'Ms.')

[comment]: <> (    suffix                                    // 'Jr.')

[comment]: <> (    name&#40;$gender = null|'male'|'female'&#41;      // 'Dr. Zane Stroman')

[comment]: <> (    firstName&#40;$gender = null|'male'|'female'&#41; // 'Maynard')

[comment]: <> (    firstNameMale                             // 'Maynard')

[comment]: <> (    firstNameFemale                           // 'Rachel')

[comment]: <> (    lastName                                  // 'Zulauf')

[comment]: <> (### `Faker\Provider\en_US\Address`)

[comment]: <> (    cityPrefix                          // 'Lake')

[comment]: <> (    secondaryAddress                    // 'Suite 961')

[comment]: <> (    state                               // 'NewMexico')

[comment]: <> (    stateAbbr                           // 'OH')

[comment]: <> (    citySuffix                          // 'borough')

[comment]: <> (    streetSuffix                        // 'Keys')

[comment]: <> (    buildingNumber                      // '484')

[comment]: <> (    city                                // 'West Judge')

[comment]: <> (    streetName                          // 'Keegan Trail')

[comment]: <> (    streetAddress                       // '439 Karley Loaf Suite 897')

[comment]: <> (    postcode                            // '17916')

[comment]: <> (    address                             // '8888 Cummings Vista Apt. 101, Susanbury, NY 95473')

[comment]: <> (    country                             // 'Falkland Islands &#40;Malvinas&#41;')

[comment]: <> (    latitude&#40;$min = -90, $max = 90&#41;     // 77.147489)

[comment]: <> (    longitude&#40;$min = -180, $max = 180&#41;  // 86.211205)

[comment]: <> (### `Faker\Provider\en_US\PhoneNumber`)

[comment]: <> (    phoneNumber             // '201-886-0269 x3767')

[comment]: <> (    tollFreePhoneNumber     // '&#40;888&#41; 937-7238')

[comment]: <> (    e164PhoneNumber     // '+27113456789')

[comment]: <> (### `Faker\Provider\en_US\Company`)

[comment]: <> (    catchPhrase             // 'Monitored regional contingency')

[comment]: <> (    bs                      // 'e-enable robust architectures')

[comment]: <> (    company                 // 'Bogan-Treutel')

[comment]: <> (    companySuffix           // 'and Sons')

[comment]: <> (    jobTitle                // 'Cashier')

[comment]: <> (### `Faker\Provider\en_US\Text`)

[comment]: <> (    realText&#40;$maxNbChars = 200, $indexSize = 2&#41; // "And yet I wish you could manage it?&#41; 'And what are they made of?' Alice asked in a shrill, passionate voice. 'Would YOU like cats if you were never even spoke to Time!' 'Perhaps not,' Alice replied.")

[comment]: <> (### `Faker\Provider\DateTime`)

[comment]: <> (    unixTime&#40;$max = 'now'&#41;                // 58781813)

[comment]: <> (    dateTime&#40;$max = 'now', $timezone = null&#41; // DateTime&#40;'2008-04-25 08:37:17', 'UTC'&#41;)

[comment]: <> (    dateTimeAD&#40;$max = 'now', $timezone = null&#41; // DateTime&#40;'1800-04-29 20:38:49', 'Europe/Paris'&#41;)

[comment]: <> (    iso8601&#40;$max = 'now'&#41;                 // '1978-12-09T10:10:29+0000')

[comment]: <> (    date&#40;$format = 'Y-m-d', $max = 'now'&#41; // '1979-06-09')

[comment]: <> (    time&#40;$format = 'H:i:s', $max = 'now'&#41; // '20:49:42')

[comment]: <> (    dateTimeBetween&#40;$startDate = '-30 years', $endDate = 'now', $timezone = null&#41; // DateTime&#40;'2003-03-15 02:00:49', 'Africa/Lagos'&#41;)

[comment]: <> (    dateTimeInInterval&#40;$startDate = '-30 years', $interval = '+ 5 days', $timezone = null&#41; // DateTime&#40;'2003-03-15 02:00:49', 'Antartica/Vostok'&#41;)

[comment]: <> (    dateTimeThisCentury&#40;$max = 'now', $timezone = null&#41;     // DateTime&#40;'1915-05-30 19:28:21', 'UTC'&#41;)

[comment]: <> (    dateTimeThisDecade&#40;$max = 'now', $timezone = null&#41;      // DateTime&#40;'2007-05-29 22:30:48', 'Europe/Paris'&#41;)

[comment]: <> (    dateTimeThisYear&#40;$max = 'now', $timezone = null&#41;        // DateTime&#40;'2011-02-27 20:52:14', 'Africa/Lagos'&#41;)

[comment]: <> (    dateTimeThisMonth&#40;$max = 'now', $timezone = null&#41;       // DateTime&#40;'2011-10-23 13:46:23', 'Antarctica/Vostok'&#41;)

[comment]: <> (    amPm&#40;$max = 'now'&#41;                    // 'pm')

[comment]: <> (    dayOfMonth&#40;$max = 'now'&#41;              // '04')

[comment]: <> (    dayOfWeek&#40;$max = 'now'&#41;               // 'Friday')

[comment]: <> (    month&#40;$max = 'now'&#41;                   // '06')

[comment]: <> (    monthName&#40;$max = 'now'&#41;               // 'January')

[comment]: <> (    year&#40;$max = 'now'&#41;                    // '1993')

[comment]: <> (    century                               // 'VI')

[comment]: <> (    timezone                              // 'Europe/Paris')

[comment]: <> (Methods accepting a `$timezone` argument default to `date_default_timezone_get&#40;&#41;`. You can pass a custom timezone string to each method, or define a custom timezone for all time methods at once using `$faker::setDefaultTimezone&#40;$timezone&#41;`.)

[comment]: <> (### `Faker\Provider\Internet`)

[comment]: <> (    email                   // 'tkshlerin@collins.com')

[comment]: <> (    safeEmail               // 'king.alford@example.org')

[comment]: <> (    freeEmail               // 'bradley72@gmail.com')

[comment]: <> (    companyEmail            // 'russel.durward@mcdermott.org')

[comment]: <> (    freeEmailDomain         // 'yahoo.com')

[comment]: <> (    safeEmailDomain         // 'example.org')

[comment]: <> (    userName                // 'wade55')

[comment]: <> (    password                // 'k&|X+a45*2[')

[comment]: <> (    domainName              // 'wolffdeckow.net')

[comment]: <> (    domainWord              // 'feeney')

[comment]: <> (    tld                     // 'biz')

[comment]: <> (    url                     // 'http://www.skilesdonnelly.biz/aut-accusantium-ut-architecto-sit-et.html')

[comment]: <> (    slug                    // 'aut-repellat-commodi-vel-itaque-nihil-id-saepe-nostrum')

[comment]: <> (    ipv4                    // '109.133.32.252')

[comment]: <> (    localIpv4               // '10.242.58.8')

[comment]: <> (    ipv6                    // '8e65:933d:22ee:a232:f1c1:2741:1f10:117c')

[comment]: <> (    macAddress              // '43:85:B7:08:10:CA')

[comment]: <> (### `Faker\Provider\UserAgent`)

[comment]: <> (    userAgent              // 'Mozilla/5.0 &#40;Windows CE&#41; AppleWebKit/5350 &#40;KHTML, like Gecko&#41; Chrome/13.0.888.0 Safari/5350')

[comment]: <> (    chrome                 // 'Mozilla/5.0 &#40;Macintosh; PPC Mac OS X 10_6_5&#41; AppleWebKit/5312 &#40;KHTML, like Gecko&#41; Chrome/14.0.894.0 Safari/5312')

[comment]: <> (    firefox                // 'Mozilla/5.0 &#40;X11; Linuxi686; rv:7.0&#41; Gecko/20101231 Firefox/3.6')

[comment]: <> (    safari                 // 'Mozilla/5.0 &#40;Macintosh; U; PPC Mac OS X 10_7_1 rv:3.0; en-US&#41; AppleWebKit/534.11.3 &#40;KHTML, like Gecko&#41; Version/4.0 Safari/534.11.3')

[comment]: <> (    opera                  // 'Opera/8.25 &#40;Windows NT 5.1; en-US&#41; Presto/2.9.188 Version/10.00')

[comment]: <> (    internetExplorer       // 'Mozilla/5.0 &#40;compatible; MSIE 7.0; Windows 98; Win 9x 4.90; Trident/3.0&#41;')

[comment]: <> (### `Faker\Provider\Payment`)

[comment]: <> (    creditCardType          // 'MasterCard')

[comment]: <> (    creditCardNumber        // '4485480221084675')

[comment]: <> (    creditCardExpirationDate // 04/13)

[comment]: <> (    creditCardExpirationDateString // '04/13')

[comment]: <> (    creditCardDetails       // array&#40;'MasterCard', '4485480221084675', 'Aleksander Nowak', '04/13'&#41;)

[comment]: <> (    // Generates a random IBAN. Set $countryCode to null for a random country)

[comment]: <> (    iban&#40;$countryCode&#41;      // 'IT31A8497112740YZ575DJ28BP4')

[comment]: <> (    swiftBicNumber          // 'RZTIAT22263')

[comment]: <> (### `Faker\Provider\Color`)

[comment]: <> (    hexcolor               // '#fa3cc2')

[comment]: <> (    rgbcolor               // '0,255,122')

[comment]: <> (    rgbColorAsArray        // array&#40;0,255,122&#41;)

[comment]: <> (    rgbCssColor            // 'rgb&#40;0,255,122&#41;')

[comment]: <> (    safeColorName          // 'fuchsia')

[comment]: <> (    colorName              // 'Gainsbor')

[comment]: <> (    hslColor               // '340,50,20')

[comment]: <> (    hslColorAsArray        // array&#40;340,50,20&#41;)

[comment]: <> (### `Faker\Provider\File`)

[comment]: <> (    fileExtension          // 'avi')

[comment]: <> (    mimeType               // 'video/x-msvideo')

[comment]: <> (    // Copy a random file from the source to the target directory and returns the fullpath or filename)

[comment]: <> (    file&#40;$sourceDir = '/tmp', $targetDir = '/tmp'&#41; // '/path/to/targetDir/13b73edae8443990be1aa8f1a483bc27.jpg')

[comment]: <> (    file&#40;$sourceDir, $targetDir, false&#41; // '13b73edae8443990be1aa8f1a483bc27.jpg')

[comment]: <> (### `Faker\Provider\Image`)

[comment]: <> (    // Image generation provided by LoremPixel &#40;http://lorempixel.com/&#41;)

[comment]: <> (    imageUrl&#40;$width = 640, $height = 480&#41; // 'http://lorempixel.com/640/480/')

[comment]: <> (    imageUrl&#40;$width, $height, 'cats'&#41;     // 'http://lorempixel.com/800/600/cats/')

[comment]: <> (    imageUrl&#40;$width, $height, 'cats', true, 'Faker'&#41; // 'http://lorempixel.com/800/400/cats/Faker')

[comment]: <> (    imageUrl&#40;$width, $height, 'cats', true, 'Faker', true&#41; // 'http://lorempixel.com/gray/800/400/cats/Faker/' Monochrome image)

[comment]: <> (    image&#40;$dir = '/tmp', $width = 640, $height = 480&#41; // '/tmp/13b73edae8443990be1aa8f1a483bc27.jpg')

[comment]: <> (    image&#40;$dir, $width, $height, 'cats'&#41;  // 'tmp/13b73edae8443990be1aa8f1a483bc27.jpg' it's a cat!)

[comment]: <> (    image&#40;$dir, $width, $height, 'cats', false&#41; // '13b73edae8443990be1aa8f1a483bc27.jpg' it's a filename without path)

[comment]: <> (    image&#40;$dir, $width, $height, 'cats', true, false&#41; // it's a no randomize images &#40;default: `true`&#41;)

[comment]: <> (    image&#40;$dir, $width, $height, 'cats', true, true, 'Faker'&#41; // 'tmp/13b73edae8443990be1aa8f1a483bc27.jpg' it's a cat with 'Faker' text. Default, `null`.)

[comment]: <> (### `Faker\Provider\Uuid`)

[comment]: <> (    uuid                   // '7e57d004-2b97-0e7a-b45f-5387367791cd')

[comment]: <> (### `Faker\Provider\Barcode`)

[comment]: <> (    ean13          // '4006381333931')

[comment]: <> (    ean8           // '73513537')

[comment]: <> (    isbn13         // '9790404436093')

[comment]: <> (    isbn10         // '4881416324')

[comment]: <> (### `Faker\Provider\Miscellaneous`)

[comment]: <> (    boolean // false)

[comment]: <> (    boolean&#40;$chanceOfGettingTrue = 50&#41; // true)

[comment]: <> (    md5           // 'de99a620c50f2990e87144735cd357e7')

[comment]: <> (    sha1          // 'f08e7f04ca1a413807ebc47551a40a20a0b4de5c')

[comment]: <> (    sha256        // '0061e4c60dac5c1d82db0135a42e00c89ae3a333e7c26485321f24348c7e98a5')

[comment]: <> (    locale        // en_UK)

[comment]: <> (    countryCode   // UK)

[comment]: <> (    languageCode  // en)

[comment]: <> (    currencyCode  // EUR)

[comment]: <> (    emoji         // 😁)

[comment]: <> (### `Faker\Provider\Biased`)

[comment]: <> (    // get a random number between 10 and 20,)

[comment]: <> (    // with more chances to be close to 20)

[comment]: <> (    biasedNumberBetween&#40;$min = 10, $max = 20, $function = 'sqrt'&#41;)

[comment]: <> (### `Faker\Provider\HtmlLorem`)

[comment]: <> (    //Generate HTML document which is no more than 2 levels deep, and no more than 3 elements wide at any level.)

[comment]: <> (    randomHtml&#40;2,3&#41;   // <html><head><title>Aut illo dolorem et accusantium eum.</title></head><body><form action="example.com" method="POST"><label for="username">sequi</label><input type="text" id="username"><label for="password">et</label><input type="password" id="password"></form><b>Id aut saepe non mollitia voluptas voluptas.</b><table><thead><tr><tr>Non consequatur.</tr><tr>Incidunt est.</tr><tr>Aut voluptatem.</tr><tr>Officia voluptas rerum quo.</tr><tr>Asperiores similique.</tr></tr></thead><tbody><tr><td>Sapiente dolorum dolorem sint laboriosam commodi qui.</td><td>Commodi nihil nesciunt eveniet quo repudiandae.</td><td>Voluptates explicabo numquam distinctio necessitatibus repellat.</td><td>Provident ut doloremque nam eum modi aspernatur.</td><td>Iusto inventore.</td></tr><tr><td>Animi nihil ratione id mollitia libero ipsa quia tempore.</td><td>Velit est officia et aut tenetur dolorem sed mollitia expedita.</td><td>Modi modi repudiandae pariatur voluptas rerum ea incidunt non molestiae eligendi eos deleniti.</td><td>Exercitationem voluptatibus dolor est iste quod molestiae.</td><td>Quia reiciendis.</td></tr><tr><td>Inventore impedit exercitationem voluptatibus rerum cupiditate.</td><td>Qui.</td><td>Aliquam.</td><td>Autem nihil aut et.</td><td>Dolor ut quia error.</td></tr><tr><td>Enim facilis iusto earum et minus rerum assumenda quis quia.</td><td>Reprehenderit ut sapiente occaecati voluptatum dolor voluptatem vitae qui velit.</td><td>Quod fugiat non.</td><td>Sunt nobis totam mollitia sed nesciunt est deleniti cumque.</td><td>Repudiandae quo.</td></tr><tr><td>Modi dicta libero quisquam doloremque qui autem.</td><td>Voluptatem aliquid saepe laudantium facere eos sunt dolor.</td><td>Est eos quis laboriosam officia expedita repellendus quia natus.</td><td>Et neque delectus quod fugit enim repudiandae qui.</td><td>Fugit soluta sit facilis facere repellat culpa magni voluptatem maiores tempora.</td></tr><tr><td>Enim dolores doloremque.</td><td>Assumenda voluptatem eum perferendis exercitationem.</td><td>Quasi in fugit deserunt ea perferendis sunt nemo consequatur dolorum soluta.</td><td>Maxime repellat qui numquam voluptatem est modi.</td><td>Alias rerum rerum hic hic eveniet.</td></tr><tr><td>Tempore voluptatem.</td><td>Eaque.</td><td>Et sit quas fugit iusto.</td><td>Nemo nihil rerum dignissimos et esse.</td><td>Repudiandae ipsum numquam.</td></tr><tr><td>Nemo sunt quia.</td><td>Sint tempore est neque ducimus harum sed.</td><td>Dicta placeat atque libero nihil.</td><td>Et qui aperiam temporibus facilis eum.</td><td>Ut dolores qui enim et maiores nesciunt.</td></tr><tr><td>Dolorum totam sint debitis saepe laborum.</td><td>Quidem corrupti ea.</td><td>Cum voluptas quod.</td><td>Possimus consequatur quasi dolorem ut et.</td><td>Et velit non hic labore repudiandae quis.</td></tr></tbody></table></body></html>)

[comment]: <> (## Modifiers)

[comment]: <> (Faker provides three special providers, `unique&#40;&#41;`, `optional&#40;&#41;`, and `valid&#40;&#41;`, to be called before any provider.)

[comment]: <> (```php)

[comment]: <> (// unique&#40;&#41; forces providers to return unique values)

[comment]: <> ($values = array&#40;&#41;;)

[comment]: <> (for &#40;$i = 0; $i < 10; $i++&#41; {)

[comment]: <> (  // get a random digit, but always a new one, to avoid duplicates)

[comment]: <> (  $values []= $faker->unique&#40;&#41;->randomDigit;)

[comment]: <> (})

[comment]: <> (print_r&#40;$values&#41;; // [4, 1, 8, 5, 0, 2, 6, 9, 7, 3])

[comment]: <> (// providers with a limited range will throw an exception when no new unique value can be generated)

[comment]: <> ($values = array&#40;&#41;;)

[comment]: <> (try {)

[comment]: <> (  for &#40;$i = 0; $i < 10; $i++&#41; {)

[comment]: <> (    $values []= $faker->unique&#40;&#41;->randomDigitNotNull;)

[comment]: <> (  })

[comment]: <> (} catch &#40;\OverflowException $e&#41; {)

[comment]: <> (  echo "There are only 9 unique digits not null, Faker can't generate 10 of them!";)

[comment]: <> (})

[comment]: <> (// you can reset the unique modifier for all providers by passing true as first argument)

[comment]: <> ($faker->unique&#40;$reset = true&#41;->randomDigitNotNull; // will not throw OverflowException since unique&#40;&#41; was reset)

[comment]: <> (// tip: unique&#40;&#41; keeps one array of values per provider)

[comment]: <> (// optional&#40;&#41; sometimes bypasses the provider to return a default value instead &#40;which defaults to NULL&#41;)

[comment]: <> ($values = array&#40;&#41;;)

[comment]: <> (for &#40;$i = 0; $i < 10; $i++&#41; {)

[comment]: <> (  // get a random digit, but also null sometimes)

[comment]: <> (  $values []= $faker->optional&#40;&#41;->randomDigit;)

[comment]: <> (})

[comment]: <> (print_r&#40;$values&#41;; // [1, 4, null, 9, 5, null, null, 4, 6, null])

[comment]: <> (// optional&#40;&#41; accepts a weight argument to specify the probability of receiving the default value.)

[comment]: <> (// 0 will always return the default value; 1 will always return the provider. Default weight is 0.5 &#40;50% chance&#41;.)

[comment]: <> ($faker->optional&#40;$weight = 0.1&#41;->randomDigit; // 90% chance of NULL)

[comment]: <> ($faker->optional&#40;$weight = 0.9&#41;->randomDigit; // 10% chance of NULL)

[comment]: <> (// optional&#40;&#41; accepts a default argument to specify the default value to return.)

[comment]: <> (// Defaults to NULL.)

[comment]: <> ($faker->optional&#40;$weight = 0.5, $default = false&#41;->randomDigit; // 50% chance of FALSE)

[comment]: <> ($faker->optional&#40;$weight = 0.9, $default = 'abc'&#41;->word; // 10% chance of 'abc')

[comment]: <> (// valid&#40;&#41; only accepts valid values according to the passed validator functions)

[comment]: <> ($values = array&#40;&#41;;)

[comment]: <> ($evenValidator = function&#40;$digit&#41; {)

[comment]: <> (	return $digit % 2 === 0;)

[comment]: <> (};)

[comment]: <> (for &#40;$i = 0; $i < 10; $i++&#41; {)

[comment]: <> (	$values []= $faker->valid&#40;$evenValidator&#41;->randomDigit;)

[comment]: <> (})

[comment]: <> (print_r&#40;$values&#41;; // [0, 4, 8, 4, 2, 6, 0, 8, 8, 6])

[comment]: <> (// just like unique&#40;&#41;, valid&#40;&#41; throws an overflow exception when it can't generate a valid value)

[comment]: <> ($values = array&#40;&#41;;)

[comment]: <> (try {)

[comment]: <> (  $faker->valid&#40;$evenValidator&#41;->randomElement&#40;[1, 3, 5, 7, 9]&#41;;)

[comment]: <> (} catch &#40;\OverflowException $e&#41; {)

[comment]: <> (  echo "Can't pick an even number in that set!";)

[comment]: <> (})

[comment]: <> (```)

[comment]: <> (If you would like to use a modifier with a value not generated by Faker, use the `passthrough&#40;&#41;` method. `passthrough&#40;&#41;` simply returns whatever value it was given.)

[comment]: <> (```php)

[comment]: <> ($faker->optional&#40;&#41;->passthrough&#40;mt_rand&#40;5, 15&#41;&#41;;)

[comment]: <> (```)

[comment]: <> (## Localization)

[comment]: <> (`Faker\Factory` can take a locale as an argument, to return localized data. If no localized provider is found, the factory fallbacks to the default locale &#40;en_US&#41;.)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> ($faker = Faker\Factory::create&#40;'fr_FR'&#41;; // create a French faker)

[comment]: <> (for &#40;$i = 0; $i < 10; $i++&#41; {)

[comment]: <> (  echo $faker->name, "\n";)

[comment]: <> (})

[comment]: <> (  // Luce du Coulon)

[comment]: <> (  // Auguste Dupont)

[comment]: <> (  // Roger Le Voisin)

[comment]: <> (  // Alexandre Lacroix)

[comment]: <> (  // Jacques Humbert-Roy)

[comment]: <> (  // Thérèse Guillet-Andre)

[comment]: <> (  // Gilles Gros-Bodin)

[comment]: <> (  // Amélie Pires)

[comment]: <> (  // Marcel Laporte)

[comment]: <> (  // Geneviève Marchal)

[comment]: <> (```)

[comment]: <> (You can check available Faker locales in the source code, [under the `Provider` directory]&#40;https://github.com/fzaninotto/Faker/tree/master/src/Faker/Provider&#41;. The localization of Faker is an ongoing process, for which we need your help. Don't hesitate to create localized providers to your own locale and submit a PR!)

[comment]: <> (## Populating Entities Using an ORM or an ODM)

[comment]: <> (Faker provides adapters for Object-Relational and Object-Document Mappers &#40;currently, [Propel]&#40;http://www.propelorm.org&#41;, [Doctrine2]&#40;http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/&#41;, [CakePHP]&#40;http://cakephp.org&#41;, [Spot2]&#40;https://github.com/vlucas/spot2&#41;, [Mandango]&#40;https://github.com/mandango/mandango&#41; and [Eloquent]&#40;https://laravel.com/docs/master/eloquent&#41; are supported&#41;. These adapters ease the population of databases through the Entity classes provided by an ORM library &#40;or the population of document stores using Document classes provided by an ODM library&#41;.)

[comment]: <> (To populate entities, create a new populator class &#40;using a generator instance as parameter&#41;, then list the class and number of all the entities that must be generated. To launch the actual data population, call the `execute&#40;&#41;` method.)

[comment]: <> (Note that some of the `populators` could require additional parameters. As example the `doctrine` populator has an option to specify)

[comment]: <> (its batchSize on how often it will flush the UnitOfWork to the database.)

[comment]: <> (Here is an example showing how to populate 5 `Author` and 10 `Book` objects:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> ($generator = \Faker\Factory::create&#40;&#41;;)

[comment]: <> ($populator = new \Faker\ORM\Propel\Populator&#40;$generator&#41;;)

[comment]: <> ($populator->addEntity&#40;'Author', 5&#41;;)

[comment]: <> ($populator->addEntity&#40;'Book', 10&#41;;)

[comment]: <> ($insertedPKs = $populator->execute&#40;&#41;;)

[comment]: <> (```)

[comment]: <> (The populator uses name and column type guessers to populate each column with relevant data. For instance, Faker populates a column named `first_name` using the `firstName` formatter, and a column with a `TIMESTAMP` type using the `dateTime` formatter. The resulting entities are therefore coherent. If Faker misinterprets a column name, you can still specify a custom closure to be used for populating a particular column, using the third argument to `addEntity&#40;&#41;`:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> ($populator->addEntity&#40;'Book', 5, array&#40;)

[comment]: <> (  'ISBN' => function&#40;&#41; use &#40;$generator&#41; { return $generator->ean13&#40;&#41;; })

[comment]: <> (&#41;&#41;;)

[comment]: <> (```)

[comment]: <> (In this example, Faker will guess a formatter for all columns except `ISBN`, for which the given anonymous function will be used.)

[comment]: <> (**Tip**: To ignore some columns, specify `null` for the column names in the third argument of `addEntity&#40;&#41;`. This is usually necessary for columns added by a behavior:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> ($populator->addEntity&#40;'Book', 5, array&#40;)

[comment]: <> (  'CreatedAt' => null,)

[comment]: <> (  'UpdatedAt' => null,)

[comment]: <> (&#41;&#41;;)

[comment]: <> (```)

[comment]: <> (Of course, Faker does not populate autoincremented primary keys. In addition, `Faker\ORM\Propel\Populator::execute&#40;&#41;` returns the list of inserted PKs, indexed by class:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (print_r&#40;$insertedPKs&#41;;)

[comment]: <> (// array&#40;)

[comment]: <> (//   'Author' => &#40;34, 35, 36, 37, 38&#41;,)

[comment]: <> (//   'Book'   => &#40;456, 457, 458, 459, 470, 471, 472, 473, 474, 475&#41;)

[comment]: <> (// &#41;)

[comment]: <> (```)

[comment]: <> (**Note:** Due to the fact that `Faker` returns all the primary keys inserted, the memory consumption will go up drastically when you do batch inserts due to the big list of data.)

[comment]: <> (In the previous example, the `Book` and `Author` models share a relationship. Since `Author` entities are populated first, Faker is smart enough to relate the populated `Book` entities to one of the populated `Author` entities.)

[comment]: <> (Lastly, if you want to execute an arbitrary function on an entity before insertion, use the fourth argument of the `addEntity&#40;&#41;` method:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> ($populator->addEntity&#40;'Book', 5, array&#40;&#41;, array&#40;)

[comment]: <> (  function&#40;$book&#41; { $book->publish&#40;&#41;; },)

[comment]: <> (&#41;&#41;;)

[comment]: <> (```)

[comment]: <> (## Seeding the Generator)

[comment]: <> (You may want to get always the same generated data - for instance when using Faker for unit testing purposes. The generator offers a `seed&#40;&#41;` method, which seeds the random number generator. Calling the same script twice with the same seed produces the same results.)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> ($faker = Faker\Factory::create&#40;&#41;;)

[comment]: <> ($faker->seed&#40;1234&#41;;)

[comment]: <> (echo $faker->name; // 'Jess Mraz I';)

[comment]: <> (```)

[comment]: <> (> **Tip**: DateTime formatters won't reproduce the same fake data if you don't fix the `$max` value:)

[comment]: <> (>)

[comment]: <> (> ```php)

[comment]: <> (> <?php)

[comment]: <> (> // even when seeded, this line will return different results because $max varies)

[comment]: <> (> $faker->dateTime&#40;&#41;; // equivalent to $faker->dateTime&#40;$max = 'now'&#41;)

[comment]: <> (> // make sure you fix the $max parameter)

[comment]: <> (> $faker->dateTime&#40;'2014-02-25 08:37:17'&#41;; // will return always the same date when seeded)

[comment]: <> (> ```)

[comment]: <> (>)

[comment]: <> (> **Tip**: Formatters won't reproduce the same fake data if you use the `rand&#40;&#41;` php function. Use `$faker` or `mt_rand&#40;&#41;` instead:)

[comment]: <> (>)

[comment]: <> (> ```php)

[comment]: <> (> <?php)

[comment]: <> (> // bad)

[comment]: <> (> $faker->realText&#40;rand&#40;10,20&#41;&#41;;)

[comment]: <> (> // good)

[comment]: <> (> $faker->realText&#40;$faker->numberBetween&#40;10,20&#41;&#41;;)

[comment]: <> (> ```)



[comment]: <> (## Faker Internals: Understanding Providers)

[comment]: <> (A `Faker\Generator` alone can't do much generation. It needs `Faker\Provider` objects to delegate the data generation to them. `Faker\Factory::create&#40;&#41;` actually creates a `Faker\Generator` bundled with the default providers. Here is what happens under the hood:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> ($faker = new Faker\Generator&#40;&#41;;)

[comment]: <> ($faker->addProvider&#40;new Faker\Provider\en_US\Person&#40;$faker&#41;&#41;;)

[comment]: <> ($faker->addProvider&#40;new Faker\Provider\en_US\Address&#40;$faker&#41;&#41;;)

[comment]: <> ($faker->addProvider&#40;new Faker\Provider\en_US\PhoneNumber&#40;$faker&#41;&#41;;)

[comment]: <> ($faker->addProvider&#40;new Faker\Provider\en_US\Company&#40;$faker&#41;&#41;;)

[comment]: <> ($faker->addProvider&#40;new Faker\Provider\Lorem&#40;$faker&#41;&#41;;)

[comment]: <> ($faker->addProvider&#40;new Faker\Provider\Internet&#40;$faker&#41;&#41;;)

[comment]: <> (````)

[comment]: <> (Whenever you try to access a property on the `$faker` object, the generator looks for a method with the same name in all the providers attached to it. For instance, calling `$faker->name` triggers a call to `Faker\Provider\Person::name&#40;&#41;`. And since Faker starts with the last provider, you can easily override existing formatters: just add a provider containing methods named after the formatters you want to override.)

[comment]: <> (That means that you can easily add your own providers to a `Faker\Generator` instance. A provider is usually a class extending `\Faker\Provider\Base`. This parent class allows you to use methods like `lexify&#40;&#41;` or `randomNumber&#40;&#41;`; it also gives you access to formatters of other providers, through the protected `$generator` property. The new formatters are the public methods of the provider class.)

[comment]: <> (Here is an example provider for populating Book data:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (namespace Faker\Provider;)

[comment]: <> (class Book extends \Faker\Provider\Base)

[comment]: <> ({)

[comment]: <> (  public function title&#40;$nbWords = 5&#41;)

[comment]: <> (  {)

[comment]: <> (    $sentence = $this->generator->sentence&#40;$nbWords&#41;;)

[comment]: <> (    return substr&#40;$sentence, 0, strlen&#40;$sentence&#41; - 1&#41;;)

[comment]: <> (  })

[comment]: <> (  public function ISBN&#40;&#41;)

[comment]: <> (  {)

[comment]: <> (    return $this->generator->ean13&#40;&#41;;)

[comment]: <> (  })

[comment]: <> (})

[comment]: <> (```)

[comment]: <> (To register this provider, just add a new instance of `\Faker\Provider\Book` to an existing generator:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> ($faker->addProvider&#40;new \Faker\Provider\Book&#40;$faker&#41;&#41;;)

[comment]: <> (```)

[comment]: <> (Now you can use the two new formatters like any other Faker formatter:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> ($book = new Book&#40;&#41;;)

[comment]: <> ($book->setTitle&#40;$faker->title&#41;;)

[comment]: <> ($book->setISBN&#40;$faker->ISBN&#41;;)

[comment]: <> ($book->setSummary&#40;$faker->text&#41;;)

[comment]: <> ($book->setPrice&#40;$faker->randomNumber&#40;2&#41;&#41;;)

[comment]: <> (```)

[comment]: <> (**Tip**: A provider can also be a Plain Old PHP Object. In that case, all the public methods of the provider become available to the generator.)

[comment]: <> (## Real Life Usage)

[comment]: <> (The following script generates a valid XML document:)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (require_once '/path/to/Faker/src/autoload.php';)

[comment]: <> ($faker = Faker\Factory::create&#40;&#41;;)

[comment]: <> (?>)

[comment]: <> (<?xml version="1.0" encoding="UTF-8"?>)

[comment]: <> (<contacts>)

[comment]: <> (<?php for &#40;$i = 0; $i < 10; $i++&#41;: ?>)

[comment]: <> (  <contact firstName="<?php echo $faker->firstName ?>" lastName="<?php echo $faker->lastName ?>" email="<?php echo $faker->email ?>">)

[comment]: <> (    <phone number="<?php echo $faker->phoneNumber ?>"/>)

[comment]: <> (<?php if &#40;$faker->boolean&#40;25&#41;&#41;: ?>)

[comment]: <> (    <birth date="<?php echo $faker->dateTimeThisCentury->format&#40;'Y-m-d'&#41; ?>" place="<?php echo $faker->city ?>"/>)

[comment]: <> (<?php endif; ?>)

[comment]: <> (    <address>)

[comment]: <> (      <street><?php echo $faker->streetAddress ?></street>)

[comment]: <> (      <city><?php echo $faker->city ?></city>)

[comment]: <> (      <postcode><?php echo $faker->postcode ?></postcode>)

[comment]: <> (      <state><?php echo $faker->state ?></state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="<?php echo $faker->company ?>" catchPhrase="<?php echo $faker->catchPhrase ?>">)

[comment]: <> (<?php if &#40;$faker->boolean&#40;33&#41;&#41;: ?>)

[comment]: <> (      <offer><?php echo $faker->bs ?></offer>)

[comment]: <> (<?php endif; ?>)

[comment]: <> (<?php if &#40;$faker->boolean&#40;33&#41;&#41;: ?>)

[comment]: <> (      <director name="<?php echo $faker->name ?>" />)

[comment]: <> (<?php endif; ?>)

[comment]: <> (    </company>)

[comment]: <> (<?php if &#40;$faker->boolean&#40;15&#41;&#41;: ?>)

[comment]: <> (    <details>)

[comment]: <> (<![CDATA[)

[comment]: <> (<?php echo $faker->text&#40;400&#41; ?>)

[comment]: <> (]]>)

[comment]: <> (    </details>)

[comment]: <> (<?php endif; ?>)

[comment]: <> (  </contact>)

[comment]: <> (<?php endfor; ?>)

[comment]: <> (</contacts>)

[comment]: <> (```)

[comment]: <> (Running this script produces a document looking like:)

[comment]: <> (```xml)

[comment]: <> (<?xml version="1.0" encoding="UTF-8"?>)

[comment]: <> (<contacts>)

[comment]: <> (  <contact firstName="Ona" lastName="Bednar" email="schamberger.frank@wuckert.com">)

[comment]: <> (    <phone number="1-265-479-1196x714"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>182 Harrison Cove</street>)

[comment]: <> (      <city>North Lloyd</city>)

[comment]: <> (      <postcode>45577</postcode>)

[comment]: <> (      <state>Alabama</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="Veum, Funk and Shanahan" catchPhrase="Function-based stable solution">)

[comment]: <> (      <offer>orchestrate compelling web-readiness</offer>)

[comment]: <> (    </company>)

[comment]: <> (    <details>)

[comment]: <> (<![CDATA[)

[comment]: <> (Alias accusantium voluptatum autem nobis cumque neque modi. Voluptatem error molestiae consequatur alias.)

[comment]: <> (Illum commodi molestiae aut repellat id. Et sit consequuntur aut et ullam asperiores. Cupiditate culpa voluptatem et mollitia dolor. Nisi praesentium qui ut.)

[comment]: <> (]]>)

[comment]: <> (    </details>)

[comment]: <> (  </contact>)

[comment]: <> (  <contact firstName="Aurelie" lastName="Paucek" email="alfonzo55@durgan.com">)

[comment]: <> (    <phone number="863.712.1363x9425"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>90111 Hegmann Inlet</street>)

[comment]: <> (      <city>South Geovanymouth</city>)

[comment]: <> (      <postcode>69961-9311</postcode>)

[comment]: <> (      <state>Colorado</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="Krajcik-Grimes" catchPhrase="Switchable cohesive instructionset">)

[comment]: <> (    </company>)

[comment]: <> (  </contact>)

[comment]: <> (  <contact firstName="Clifton" lastName="Kshlerin" email="kianna.wiegand@framiwyman.info">)

[comment]: <> (    <phone number="692-194-4746"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>9791 Nona Corner</street>)

[comment]: <> (      <city>Harberhaven</city>)

[comment]: <> (      <postcode>74062-8191</postcode>)

[comment]: <> (      <state>RhodeIsland</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="Rosenbaum-Aufderhar" catchPhrase="Realigned asynchronous encryption">)

[comment]: <> (    </company>)

[comment]: <> (  </contact>)

[comment]: <> (  <contact firstName="Alexandre" lastName="Orn" email="thelma37@erdmancorwin.biz">)

[comment]: <> (    <phone number="189.655.8677x027"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>11161 Schultz Via</street>)

[comment]: <> (      <city>Feilstad</city>)

[comment]: <> (      <postcode>98019</postcode>)

[comment]: <> (      <state>NewJersey</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="O'Hara-Prosacco" catchPhrase="Re-engineered solution-oriented algorithm">)

[comment]: <> (      <director name="Dr. Berenice Auer V" />)

[comment]: <> (    </company>)

[comment]: <> (    <details>)

[comment]: <> (<![CDATA[)

[comment]: <> (Ut itaque et quaerat doloremque eum praesentium. Rerum in saepe dolorem. Explicabo qui consequuntur commodi minima rem.)

[comment]: <> (Harum temporibus rerum dolores. Non molestiae id dolorem placeat.)

[comment]: <> (Aut asperiores nihil eius repellendus. Vero nihil corporis voluptatem explicabo commodi. Occaecati omnis blanditiis beatae quod aspernatur eos.)

[comment]: <> (]]>)

[comment]: <> (    </details>)

[comment]: <> (  </contact>)

[comment]: <> (  <contact firstName="Katelynn" lastName="Kohler" email="reinger.trudie@stiedemannjakubowski.com">)

[comment]: <> (    <phone number="&#40;665&#41;713-1657"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>6106 Nader Village Suite 753</street>)

[comment]: <> (      <city>McLaughlinstad</city>)

[comment]: <> (      <postcode>43189-8621</postcode>)

[comment]: <> (      <state>Missouri</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="Herman-Tremblay" catchPhrase="Object-based explicit service-desk">)

[comment]: <> (      <offer>expedite viral synergies</offer>)

[comment]: <> (      <director name="Arden Deckow" />)

[comment]: <> (    </company>)

[comment]: <> (  </contact>)

[comment]: <> (  <contact firstName="Blanca" lastName="Stark" email="tad27@feest.net">)

[comment]: <> (    <phone number="168.719.4692x87177"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>7546 Kuvalis Plaza</street>)

[comment]: <> (      <city>South Wilfrid</city>)

[comment]: <> (      <postcode>77069</postcode>)

[comment]: <> (      <state>Georgia</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="Upton, Braun and Rowe" catchPhrase="Visionary leadingedge pricingstructure">)

[comment]: <> (    </company>)

[comment]: <> (  </contact>)

[comment]: <> (  <contact firstName="Rene" lastName="Spencer" email="anibal28@armstrong.info">)

[comment]: <> (    <phone number="715.222.0095x175"/>)

[comment]: <> (    <birth date="2008-08-07" place="Zulaufborough"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>478 Daisha Landing Apt. 510</street>)

[comment]: <> (      <city>West Lizethhaven</city>)

[comment]: <> (      <postcode>30566-5362</postcode>)

[comment]: <> (      <state>WestVirginia</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="Wiza Inc" catchPhrase="Persevering reciprocal approach">)

[comment]: <> (      <offer>orchestrate dynamic networks</offer>)

[comment]: <> (      <director name="Erwin Nienow" />)

[comment]: <> (    </company>)

[comment]: <> (    <details>)

[comment]: <> (<![CDATA[)

[comment]: <> (Dolorem consequatur voluptates unde optio unde. Accusantium dolorem est est architecto impedit. Corrupti et provident quo.)

[comment]: <> (Reprehenderit dolores aut quidem suscipit repudiandae corporis error. Molestiae enim aperiam illo.)

[comment]: <> (Et similique qui non expedita quia dolorum. Ex rem incidunt ea accusantium temporibus minus non.)

[comment]: <> (]]>)

[comment]: <> (    </details>)

[comment]: <> (  </contact>)

[comment]: <> (  <contact firstName="Alessandro" lastName="Hagenes" email="tbreitenberg@oharagorczany.com">)

[comment]: <> (    <phone number="1-284-958-6768"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>1251 Koelpin Mission</street>)

[comment]: <> (      <city>North Revastad</city>)

[comment]: <> (      <postcode>81620</postcode>)

[comment]: <> (      <state>Maryland</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="Stiedemann-Bruen" catchPhrase="Re-engineered 24/7 success">)

[comment]: <> (    </company>)

[comment]: <> (  </contact>)

[comment]: <> (  <contact firstName="Novella" lastName="Rutherford" email="claud65@bogisich.biz">)

[comment]: <> (    <phone number="&#40;091&#41;825-7971"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>6396 Langworth Hills Apt. 446</street>)

[comment]: <> (      <city>New Carlos</city>)

[comment]: <> (      <postcode>89399-0268</postcode>)

[comment]: <> (      <state>Wyoming</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="Stroman-Legros" catchPhrase="Expanded 4thgeneration moratorium">)

[comment]: <> (      <director name="Earlene Bayer" />)

[comment]: <> (    </company>)

[comment]: <> (  </contact>)

[comment]: <> (  <contact firstName="Andreane" lastName="Mann" email="meggie17@ornbaumbach.com">)

[comment]: <> (    <phone number="941-659-9982x5689"/>)

[comment]: <> (    <birth date="1934-02-21" place="Stantonborough"/>)

[comment]: <> (    <address>)

[comment]: <> (      <street>2246 Kreiger Station Apt. 291</street>)

[comment]: <> (      <city>Kaydenmouth</city>)

[comment]: <> (      <postcode>11397-1072</postcode>)

[comment]: <> (      <state>Wyoming</state>)

[comment]: <> (    </address>)

[comment]: <> (    <company name="Lebsack, Bernhard and Kiehn" catchPhrase="Persevering actuating framework">)

[comment]: <> (      <offer>grow sticky portals</offer>)

[comment]: <> (    </company>)

[comment]: <> (    <details>)

[comment]: <> (<![CDATA[)

[comment]: <> (Quia dolor ut quia error libero. Enim facilis iusto earum et minus rerum assumenda. Quia doloribus et reprehenderit ut. Occaecati voluptatum dolor voluptatem vitae qui velit quia.)

[comment]: <> (Fugiat non in itaque sunt nobis totam. Sed nesciunt est deleniti cumque alias. Repudiandae quo aut numquam modi dicta libero.)

[comment]: <> (]]>)

[comment]: <> (    </details>)

[comment]: <> (  </contact>)

[comment]: <> (</contacts>)

[comment]: <> (```)

[comment]: <> (## Language specific formatters)

[comment]: <> (### `Faker\Provider\ar_SA\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->idNumber;      // ID number)

[comment]: <> (echo $faker->nationalIdNumber // Citizen ID number)

[comment]: <> (echo $faker->foreignerIdNumber // Foreigner ID number)

[comment]: <> (echo $faker->companyIdNumber // Company ID number)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ar_SA\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->bankAccountNumber // "SA0218IBYZVZJSEC8536V4XC")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\at_AT\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->vat;           // "AT U12345678" - Austrian Value Added Tax number)

[comment]: <> (echo $faker->vat&#40;false&#41;;    // "ATU12345678" - unspaced Austrian Value Added Tax number)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\bg_BG\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->vat;           // "BG 0123456789" - Bulgarian Value Added Tax number)

[comment]: <> (echo $faker->vat&#40;false&#41;;    // "BG0123456789" - unspaced Bulgarian Value Added Tax number)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\cs_CZ\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->region; // "Liberecký kraj")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\cs_CZ\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a valid IČO)

[comment]: <> (echo $faker->ico; // "69663963")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\cs_CZ\DateTime`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->monthNameGenitive; // "prosince")

[comment]: <> (echo $faker->formattedDate; // "12. listopadu 2015")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\cs_CZ\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->birthNumber; // "7304243452")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\da_DK\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random CPR number)

[comment]: <> (echo $faker->cpr; // "051280-2387")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\da_DK\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random 'kommune' name)

[comment]: <> (echo $faker->kommune; // "Frederiksberg")

[comment]: <> (// Generates a random region name)

[comment]: <> (echo $faker->region; // "Region Sjælland")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\da_DK\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random CVR number)

[comment]: <> (echo $faker->cvr; // "32458723")

[comment]: <> (// Generates a random P number)

[comment]: <> (echo $faker->p; // "5398237590")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\de_CH\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random AVS13/AHV13 social security number)

[comment]: <> (echo $faker->avs13; // "756.1234.5678.97" OR)

[comment]: <> (echo $faker->ahv13; // "756.1234.5678.97")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\de_DE\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->bankAccountNumber; // "DE41849025553661169313")

[comment]: <> (echo $faker->bank; // "Volksbank Stuttgart")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_HK\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a fake town name based on the words commonly found in Hong Kong)

[comment]: <> (echo $faker->town; // "Yuen Long")

[comment]: <> (// Generates a fake village name based on the words commonly found in Hong Kong)

[comment]: <> (echo $faker->village; // "O Tau")

[comment]: <> (// Generates a fake estate name based on the words commonly found in Hong Kong)

[comment]: <> (echo $faker->estate; // "Ching Lai Court")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_HK\Phone`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a Hong Kong mobile number &#40;starting with 5, 6 or 9&#41;)

[comment]: <> (echo $faker->mobileNumber; // "92150087")

[comment]: <> (// Generates a Hong Kong landline number &#40;starting with 2 or 3&#41;)

[comment]: <> (echo $faker->landlineNumber; // "32750132")

[comment]: <> (// Generates a Hong Kong fax number &#40;starting with 7&#41;)

[comment]: <> (echo $faker->faxNumber; // "71937729")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_NG\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random region name)

[comment]: <> (echo $faker->region; // 'Katsina')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_NG\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random person name)

[comment]: <> (echo $faker->name; // 'Oluwunmi Mayowa')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_NZ\Phone`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a cell &#40;mobile&#41; phone number)

[comment]: <> (echo $faker->mobileNumber; // "021 123 4567")

[comment]: <> (// Generates a toll free number)

[comment]: <> (echo $faker->tollFreeNumber; // "0800 123 456")

[comment]: <> (// Area Code)

[comment]: <> (echo $faker->areaCode; // "03")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_US\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generate a random Employer Identification Number)

[comment]: <> (echo $faker->ein; // '12-3456789')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_US\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->bankAccountNumber;  // '51915734310')

[comment]: <> (echo $faker->bankRoutingNumber;  // '212240302')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_US\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random Social Security Number)

[comment]: <> (echo $faker->ssn; // '123-45-6789')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_ZA\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random company registration number)

[comment]: <> (echo $faker->companyNumber; // 1999/789634/01)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_ZA\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random national identification number)

[comment]: <> (echo $faker->idNumber; // 6606192211041)

[comment]: <> (// Generates a random valid licence code)

[comment]: <> (echo $faker->licenceCode; // EB)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\en_ZA\PhoneNumber`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a special rate toll free phone number)

[comment]: <> (echo $faker->tollFreeNumber; // 0800 555 5555)

[comment]: <> (// Generates a mobile phone number)

[comment]: <> (echo $faker->mobileNumber; // 082 123 5555)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\es_ES\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a Documento Nacional de Identidad &#40;DNI&#41; number)

[comment]: <> (echo $faker->dni; // '77446565E')

[comment]: <> (// Generates a random valid licence code)

[comment]: <> (echo $faker->licenceCode; // B)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\es_ES\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a Código de identificación Fiscal &#40;CIF&#41; number)

[comment]: <> (echo $faker->vat;           // "A35864370")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\es_ES\PhoneNumber`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a special rate toll free phone number)

[comment]: <> (echo $faker->tollFreeNumber; // 900 123 456)

[comment]: <> (// Generates a mobile phone number)

[comment]: <> (echo $faker->mobileNumber; // +34 612 12 24)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\es_PE\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a Peruvian Documento Nacional de Identidad &#40;DNI&#41; number)

[comment]: <> (echo $faker->dni; // '83367512')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fa_IR\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a valid nationalCode)

[comment]: <> (echo $faker->nationalCode; // "0078475759")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fa_IR\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random building name)

[comment]: <> (echo $faker->building; // "ساختمان آفتاب")

[comment]: <> (// Returns a random city name)

[comment]: <> (echo $faker->city // "استان زنجان")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fa_IR\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random contract type)

[comment]: <> (echo $faker->contract; // "رسمی")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fi_FI\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank account number)

[comment]: <> (echo $faker->bankAccountNumber; // "FI8350799879879616")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fi_FI\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (//Generates a valid Finnish personal identity number &#40;in Finnish - Henkilötunnus&#41;)

[comment]: <> (echo $faker->personalIdentityNumber&#40;&#41; // '170974-007J')

[comment]: <> (//Since the numbers are different for male and female persons, optionally you can specify gender.)

[comment]: <> (echo $faker->personalIdentityNumber&#40;\DateTime::createFromFormat&#40;'Y-m-d', '2015-12-14'&#41;, 'female'&#41; // '141215A520B')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fr_BE\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->vat;           // "BE 0123456789" - Belgian Value Added Tax number)

[comment]: <> (echo $faker->vat&#40;false&#41;;    // "BE0123456789" - unspaced Belgian Value Added Tax number)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\es_VE\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generate a Cédula de identidad number, you can pass one argument to add separator)

[comment]: <> (echo $faker->nationalId; // 'V11223344')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\es_VE\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a R.I.F. number, you can pass one argument to add separators)

[comment]: <> (echo $faker->taxpayerIdentificationNumber; // 'J1234567891')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fr_CH\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random AVS13/AHV13 social security number)

[comment]: <> (echo $faker->avs13; // "756.1234.5678.97")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fr_FR\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random department name)

[comment]: <> (echo $faker->departmentName; // "Haut-Rhin")

[comment]: <> (// Generates a random department number)

[comment]: <> (echo $faker->departmentNumber; // "2B")

[comment]: <> (// Generates a random department info &#40;department number => department name&#41;)

[comment]: <> ($faker->department; // array&#40;'18' => 'Cher'&#41;;)

[comment]: <> (// Generates a random region)

[comment]: <> (echo $faker->region; // "Saint-Pierre-et-Miquelon")

[comment]: <> (// Generates a random appartement,stair)

[comment]: <> (echo $faker->secondaryAddress; // "Bat. 961")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fr_FR\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random SIREN number)

[comment]: <> (echo $faker->siren; // 082 250 104)

[comment]: <> (// Generates a random SIRET number)

[comment]: <> (echo $faker->siret; // 347 355 708 00224)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fr_FR\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random VAT)

[comment]: <> (echo $faker->vat; // FR 12 123 456 789)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fr_FR\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random NIR / Sécurité Sociale number)

[comment]: <> (echo $faker->nir; // 1 88 07 35 127 571 - 19)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\fr_FR\PhoneNumber`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates phone numbers)

[comment]: <> (echo $faker->phoneNumber; // +33 &#40;0&#41;1 67 97 01 31)

[comment]: <> (echo $faker->mobileNumber; // +33 6 21 12 72 84)

[comment]: <> (echo $faker->serviceNumber // 08 98 04 84 46)

[comment]: <> (```)


[comment]: <> (### `Faker\Provider\he_IL\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->bankAccountNumber // "IL392237392219429527697")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\hr_HR\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->bankAccountNumber // "HR3789114847226078672")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\hu_HU\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank account number)

[comment]: <> (echo $faker->bankAccountNumber; // "HU09904437680048220079300783")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\id_ID\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random Nomor Induk Kependudukan &#40;NIK&#41;)

[comment]: <> (// first argument is gender, either Person::GENDER_MALE or Person::GENDER_FEMALE, if none specified random gender is used)

[comment]: <> (// second argument is birth date &#40;DateTime object&#41;, if none specified, random birth date is used)

[comment]: <> (echo $faker->nik&#40;&#41;; // "8522246001570940")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\it_CH\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random AVS13/AHV13 social security number)

[comment]: <> (echo $faker->avs13; // "756.1234.5678.97")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\it_IT\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random Vat Id)

[comment]: <> (echo $faker->vatId&#40;&#41;; // "IT98746784967")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\it_IT\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random Tax Id code &#40;Codice fiscale&#41;)

[comment]: <> (echo $faker->taxId&#40;&#41;; // "DIXDPZ44E08F367A")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ja_JP\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a 'kana' name)

[comment]: <> (echo $faker->kanaName&#40;$gender = null|'male'|'female'&#41; // "アオタ ミノル")

[comment]: <> (// Generates a 'kana' first name)

[comment]: <> (echo $faker->firstKanaName&#40;$gender = null|'male'|'female'&#41; // "ヒデキ")

[comment]: <> (// Generates a 'kana' first name on the male)

[comment]: <> (echo $faker->firstKanaNameMale // "ヒデキ")

[comment]: <> (// Generates a 'kana' first name on the female)

[comment]: <> (echo $faker->firstKanaNameFemale // "マアヤ")

[comment]: <> (// Generates a 'kana' last name)

[comment]: <> (echo $faker->lastKanaName; // "ナカジマ")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ka_GE\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank account number)

[comment]: <> (echo $faker->bankAccountNumber; // "GE33ZV9773853617253389")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\kk_KZ\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates an business identification number)

[comment]: <> (echo $faker->businessIdentificationNumber; // "150140000019")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\kk_KZ\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank name)

[comment]: <> (echo $faker->bank; // "Қазкоммерцбанк")

[comment]: <> (// Generates a random bank account number)

[comment]: <> (echo $faker->bankAccountNumber; // "KZ1076321LO4H6X41I37")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\kk_KZ\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates an individual identification number)

[comment]: <> (echo $faker->individualIdentificationNumber; // "780322300455")

[comment]: <> (// Generates an individual identification number based on his/her birth date)

[comment]: <> (echo $faker->individualIdentificationNumber&#40;new \DateTime&#40;'1999-03-01'&#41;&#41;; // "990301300455")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ko_KR\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a metropolitan city)

[comment]: <> (echo $faker->metropolitanCity; // "서울특별시")

[comment]: <> (// Generates a borough)

[comment]: <> (echo $faker->borough; // "강남구")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ko_KR\PhoneNumber`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a local area phone numer)

[comment]: <> (echo $faker->localAreaPhoneNumber; // "02-1234-4567")

[comment]: <> (// Generates a cell phone number)

[comment]: <> (echo $faker->cellPhoneNumber; // "010-9876-5432")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\lt_LT\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->bankAccountNumber // "LT300848876740317118")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\lv_LV\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random personal identity card number)

[comment]: <> (echo $faker->personalIdentityNumber; // "140190-12301")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ms_MY\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random Malaysian township)

[comment]: <> (echo $faker->township; // "Taman Bahagia")

[comment]: <> (// Generates a random Malaysian town address with matching postcode and state)

[comment]: <> (echo $faker->townState; // "55100 Bukit Bintang, Kuala Lumpur")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ms_MY\Miscellaneous`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random vehicle license plate number)

[comment]: <> (echo $faker->jpjNumberPlate; // "WPL 5169")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ms_MY\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random Malaysian bank)

[comment]: <> (echo $faker->bank; // "Maybank")

[comment]: <> (// Generates a random Malaysian bank account number &#40;10-16 digits&#41;)

[comment]: <> (echo $faker->bankAccountNumber; // "1234567890123456")

[comment]: <> (// Generates a random Malaysian insurance company)

[comment]: <> (echo $faker->insurance; // "AIA Malaysia")

[comment]: <> (// Generates a random Malaysian bank SWIFT Code)

[comment]: <> (echo $faker->swiftCode; // "MBBEMYKLXXX")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ms_MY\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random personal identity card &#40;myKad&#41; number)

[comment]: <> (echo $faker->myKadNumber&#40;$gender = null|'male'|'female', $hyphen = null|true|false&#41;; // "710703471796")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ms_MY\PhoneNumber`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random Malaysian mobile number)

[comment]: <> (echo $faker->mobileNumber&#40;$countryCodePrefix = null|true|false, $formatting = null|true|false&#41;; // "+6012-705 3767")

[comment]: <> (// Generates a random Malaysian landline number)

[comment]: <> (echo $faker->fixedLineNumber&#40;$countryCodePrefix = null|true|false, $formatting = null|true|false&#41;; // "03-7112 0455")

[comment]: <> (// Generates a random Malaysian voip number)

[comment]: <> (echo $faker->voipNumber&#40;$countryCodePrefix = null|true|false, $formatting = null|true|false&#41;; // "015-458 7099")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ne_NP\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (//Generates a Nepali district name)

[comment]: <> (echo $faker->district;)

[comment]: <> (//Generates a Nepali city name)

[comment]: <> (echo $faker->cityName;)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\nl_BE\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->vat;           // "BE 0123456789" - Belgian Value Added Tax number)

[comment]: <> (echo $faker->vat&#40;false&#41;;    // "BE0123456789" - unspaced Belgian Value Added Tax number)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\nl_BE\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->rrn&#40;&#41;;         // "83051711784" - Belgian Rijksregisternummer)

[comment]: <> (echo $faker->rrn&#40;'female'&#41;; // "50032089858" - Belgian Rijksregisternummer for a female)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\nl_NL\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->jobTitle; // "Houtbewerker")

[comment]: <> (echo $faker->vat; // "NL123456789B01" - Dutch Value Added Tax number)

[comment]: <> (echo $faker->btw; // "NL123456789B01" - Dutch Value Added Tax number &#40;alias&#41;)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\nl_NL\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->idNumber; // "111222333" - Dutch Personal identification number &#40;BSN&#41;)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\nb_NO\MobileNumber`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random Norwegian mobile phone number)

[comment]: <> (echo $faker->mobileNumber; // "+4799988777")

[comment]: <> (echo $faker->mobileNumber; // "999 88 777")

[comment]: <> (echo $faker->mobileNumber; // "99988777")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\nb_NO\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank account number)

[comment]: <> (echo $faker->bankAccountNumber; // "NO3246764709816")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\pl_PL\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random PESEL number)

[comment]: <> (echo $faker->pesel; // "40061451555")

[comment]: <> (// Generates a random personal identity card number)

[comment]: <> (echo $faker->personalIdentityNumber; // "AKX383360")

[comment]: <> (// Generates a random taxpayer identification number &#40;NIP&#41;)

[comment]: <> (echo $faker->taxpayerIdentificationNumber; // '8211575109')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\pl_PL\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random REGON number)

[comment]: <> (echo $faker->regon; // "714676680")

[comment]: <> (// Generates a random local REGON number)

[comment]: <> (echo $faker->regonLocal; // "15346111382836")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\pl_PL\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank name)

[comment]: <> (echo $faker->bank; // "Narodowy Bank Polski")

[comment]: <> (// Generates a random bank account number)

[comment]: <> (echo $faker->bankAccountNumber; // "PL14968907563953822118075816")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\pt_PT\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random taxpayer identification number &#40;in portuguese - Número de Identificação Fiscal NIF&#41;)

[comment]: <> (echo $faker->taxpayerIdentificationNumber; // '165249277')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\pt_BR\Address`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random region name)

[comment]: <> (echo $faker->region; // 'Nordeste')

[comment]: <> (// Generates a random region abbreviation)

[comment]: <> (echo $faker->regionAbbr; // 'NE')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\pt_BR\PhoneNumber`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (echo $faker->areaCode;  // 21)

[comment]: <> (echo $faker->cellphone; // 9432-5656)

[comment]: <> (echo $faker->landline;  // 2654-3445)

[comment]: <> (echo $faker->phone;     // random landline, 8-digit or 9-digit cellphone number)

[comment]: <> (// Using the phone functions with a false argument returns unformatted numbers)

[comment]: <> (echo $faker->cellphone&#40;false&#41;; // 74336667)

[comment]: <> (// cellphone&#40;&#41; has a special second argument to add the 9th digit. Ignored if generated a Radio number)

[comment]: <> (echo $faker->cellphone&#40;true, true&#41;; // 98983-3945 or 7343-1290)

[comment]: <> (// Using the "Number" suffix adds area code to the phone)

[comment]: <> (echo $faker->cellphoneNumber;       // &#40;11&#41; 98309-2935)

[comment]: <> (echo $faker->landlineNumber&#40;false&#41;; // 3522835934)

[comment]: <> (echo $faker->phoneNumber;           // formatted, random landline or cellphone &#40;obeying the 9th digit rule&#41;)

[comment]: <> (echo $faker->phoneNumberCleared;    // not formatted, random landline or cellphone &#40;obeying the 9th digit rule&#41;)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\pt_BR\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// The name generator may include double first or double last names, plus title and suffix)

[comment]: <> (echo $faker->name; // 'Sr. Luis Adriano Sepúlveda Filho')

[comment]: <> (// Valid document generators have a boolean argument to remove formatting)

[comment]: <> (echo $faker->cpf;        // '145.343.345-76')

[comment]: <> (echo $faker->cpf&#40;false&#41;; // '45623467866')

[comment]: <> (echo $faker->rg;         // '84.405.736-3')

[comment]: <> (echo $faker->rg&#40;false&#41;;  // '844057363')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\pt_BR\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a Brazilian formatted and valid CNPJ)

[comment]: <> (echo $faker->cnpj;        // '23.663.478/0001-24')

[comment]: <> (echo $faker->cnpj&#40;false&#41;; // '23663478000124')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ro_MD\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank account number)

[comment]: <> (echo $faker->bankAccountNumber; // "MD83BQW1CKMUW34HBESDP3A8")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ro_RO\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank account number)

[comment]: <> (echo $faker->bankAccountNumber; // "RO55WRJE3OE8X3YQI7J26U1E")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ro_RO\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random male name prefix/title)

[comment]: <> (echo $faker->prefixMale; // "ing.")

[comment]: <> (// Generates a random female name prefix/title)

[comment]: <> (echo $faker->prefixFemale; // "d-na.")

[comment]: <> (// Generates a random male first name)

[comment]: <> (echo $faker->firstNameMale; // "Adrian")

[comment]: <> (// Generates a random female first name)

[comment]: <> (echo $faker->firstNameFemale; // "Miruna")


[comment]: <> (// Generates a random Personal Numerical Code &#40;CNP&#41;)

[comment]: <> (echo $faker->cnp; // "2800523081231")

[comment]: <> (// Valid option values:)

[comment]: <> (//    $gender: null &#40;random&#41;, male, female)

[comment]: <> (//    $dateOfBirth &#40;1800+&#41;: null &#40;random&#41;, Y-m-d, Y-m &#40;random day&#41;, Y &#40;random month and day&#41;)

[comment]: <> (//          i.e. '1981-06-16', '2015-03', '1900')

[comment]: <> (//    $county: 2 letter ISO 3166-2:RO county codes and B1, B2, B3, B4, B5, B6 for Bucharest's 6 sectors)

[comment]: <> (//    $isResident true/false flag if the person resides in Romania)

[comment]: <> (echo $faker->cnp&#40;$gender = null, $dateOfBirth = null, $county = null, $isResident = true&#41;;)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ro_RO\PhoneNumber`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random toll-free phone number)

[comment]: <> (echo $faker->tollFreePhoneNumber; // "0800123456")

[comment]: <> (// Generates a random premium-rate phone number)

[comment]: <> (echo $faker->premiumRatePhoneNumber; // "0900123456")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\ru_RU\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a Russian bank name &#40;based on list of real russian banks&#41;)

[comment]: <> (echo $faker->bank; // "ОТП Банк")

[comment]: <> (//Generate a Russian Tax Payment Number for Company)

[comment]: <> (echo $faker->inn; //  7813540735)

[comment]: <> (//Generate a Russian Tax Code for Company)

[comment]: <> (echo $faker->kpp; // 781301001)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\sv_SE\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank account number)

[comment]: <> (echo $faker->bankAccountNumber; // "SE5018548608468284909192")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\sv_SE\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (//Generates a valid Swedish personal identity number &#40;in Swedish - Personnummer&#41;)

[comment]: <> (echo $faker->personalIdentityNumber&#40;&#41; // '950910-0799')

[comment]: <> (//Since the numbers are different for male and female persons, optionally you can specify gender.)

[comment]: <> (echo $faker->personalIdentityNumber&#40;'female'&#41; // '950910-0781')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\tr_TR\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (//Generates a valid Turkish identity number &#40;in Turkish - T.C. Kimlik No&#41;)

[comment]: <> (echo $faker->tcNo // '55300634882')

[comment]: <> (```)


[comment]: <> (### `Faker\Provider\zh_CN\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random bank name &#40;based on list of real chinese banks&#41;)

[comment]: <> (echo $faker->bank; // '中国建设银行')

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\uk_UA\Payment`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates an Ukraine bank name &#40;based on list of real Ukraine banks&#41;)

[comment]: <> (echo $faker->bank; // "Ощадбанк")

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\zh_TW\Person`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random personal identify number)

[comment]: <> (echo $faker->personalIdentityNumber; // A223456789)

[comment]: <> (```)

[comment]: <> (### `Faker\Provider\zh_TW\Company`)

[comment]: <> (```php)

[comment]: <> (<?php)

[comment]: <> (// Generates a random VAT / Company Tax number)

[comment]: <> (echo $faker->VAT; //23456789)

[comment]: <> (```)


[comment]: <> (## Third-Party Libraries Extending/Based On Faker)

[comment]: <> (* Symfony bundles:)

[comment]: <> (  * [`willdurand/faker-bundle`]&#40;https://github.com/willdurand/BazingaFakerBundle&#41;: Put the awesome Faker library into the Symfony2 DIC and populate your database with fake data.)

[comment]: <> (  * [`hautelook/alice-bundle`]&#40;https://github.com/hautelook/AliceBundle&#41;, [`h4cc/alice-fixtures-bundle`]&#40;https://github.com/h4cc/AliceFixturesBundle&#41;: Bundles for using [`nelmio/alice`]&#40;https://packagist.org/packages/nelmio/alice&#41; and Faker with data fixtures. Able to use Doctrine ORM as well as Doctrine MongoDB ODM.)

[comment]: <> (* [`emanueleminotto/faker-service-provider`]&#40;https://github.com/EmanueleMinotto/FakerServiceProvider&#41;: Faker Service Provider for Silex)

[comment]: <> (* [`bit3/faker-cli`]&#40;https://github.com/bit3/faker-cli&#41;: Command Line Tool for the Faker PHP library)

[comment]: <> (* [`league/factory-muffin`]&#40;https://github.com/thephpleague/factory-muffin&#41;: enable the rapid creation of objects &#40;PHP port of factory-girl&#41;)

[comment]: <> (* [`fzaninotto/company-name-generator`]&#40;https://github.com/fzaninotto/CompanyNameGenerator&#41;: Generate names for English tech companies with class)

[comment]: <> (* [`emanueleminotto/faker-placehold-it-provider`]&#40;https://github.com/EmanueleMinotto/PlaceholdItProvider&#41;: Generate images using placehold.it)

[comment]: <> (* [`spyrit/datalea`]&#40;https://github.com/spyrit/datalea&#41; A highly customizable random test data generator web app)

[comment]: <> (* [`frequenc1/newage-ipsum`]&#40;https://github.com/frequenc1/newage-ipsum&#41;: A new aged ipsum provider for the faker library inspired by http://sebpearce.com/bullshit/)

[comment]: <> (* [`prewk/xml-faker`]&#40;https://github.com/prewk/xml-faker&#41;: Create fake XML with Faker)

[comment]: <> (* [`denheck/faker-context`]&#40;https://github.com/denheck/faker-context&#41;: Behat context using Faker to generate testdata)

[comment]: <> (* [`swekaj/cron-expression-generator`]&#40;https://github.com/swekaj/CronExpressionGenerator&#41;: Faker provider for generating random, valid cron expressions.)

[comment]: <> (* [`pragmafabrik/pomm-faker`]&#40;https://github.com/pragmafabrik/Pomm2Faker&#41;: Faker client for Pomm database framework &#40;PostgreSQL&#41;)

[comment]: <> (* [`nelmio/alice`]&#40;https://github.com/nelmio/alice&#41;: Fixtures/object generator with a yaml DSL that can use Faker as data generator.)

[comment]: <> (* [`ravage84/cakephp-fake-seeder`]&#40;https://github.com/ravage84/cakephp-fake-seeder&#41; A CakePHP 2.x shell to seed your database with fake and/or fixed data.)

[comment]: <> (* [`bheller/images-generator`]&#40;https://github.com/bruceheller/images-generator&#41;: An image generator provider using GD for placeholder type pictures)

[comment]: <> (* [`pattern-lab/plugin-faker`]&#40;https://github.com/pattern-lab/plugin-php-faker&#41;: Pattern Lab is a Styleguide, Component Library, and Prototyping tool. This creates unique content each time Pattern Lab is generated.)

[comment]: <> (* [`guidocella/eloquent-populator`]&#40;https://github.com/guidocella/eloquent-populator&#41;: Adapter for Laravel's Eloquent ORM.)

[comment]: <> (* [`tamperdata/exiges`]&#40;https://github.com/tamperdata/exiges&#41;: Faker provider for generating random temperatures)

[comment]: <> (* [`jzonta/faker-restaurant`]&#40;https://github.com/jzonta/FakerRestaurant&#41;: Faker for Food and Beverage names generate)

[comment]: <> (* [`aalaap/faker-youtube`]&#40;https://github.com/aalaap/faker-youtube&#41;: Faker for YouTube URLs in various formats)

[comment]: <> (* [`pelmered/fake-car`]&#40;https://github.com/pelmered/fake-car&#41;: Faker for cars and car data)

[comment]: <> (* [`bluemmb/faker-picsum-photos-provider`]&#40;https://github.com/bluemmb/Faker-PicsumPhotos&#41;: Generate images using [picsum.photos]&#40;http://picsum.photos/&#41;)

[comment]: <> (* [`er1z/fakemock`]&#40;https://github.com/er1z/fakemock&#41;: Generate mocks using class-configuration and detection via Faker's guesser and Symfony asserts)

[comment]: <> (* [`xvladqt/faker-lorem-flickr`]&#40;https://github.com/xvladxtremal/Faker-LoremFlickr&#41;: Generate images using [loremflickr.com]&#40;http://loremflickr.com/&#41;)

[comment]: <> (* [`metrakit/faker-eddy-malou`]&#40;https://github.com/Metrakit/faker-eddy-malou&#41;: Generate French Eddy Malou sentences & paragraphs)

[comment]: <> (* [`drupol/belgian-national-number-faker`]&#40;https://github.com/drupol/belgian-national-number-faker&#41;: Generate fake Belgian national numbers)

[comment]: <> (* [`elgentos/masquerade`]&#40;https://github.com/elgentos/masquerade&#41;: Configuration-based, platform-agnostic, locale-compatible data faker tool &#40;out-of-the-box support for Magento 2&#41;)

[comment]: <> (* [`ottaviano/faker-gravatar`]&#40;https://github.com/ottaviano/faker-gravatar&#41;: Generate avatars using [Gravatar]&#40;https://en.gravatar.com/site/implement/images/&#41;)

[comment]: <> (* [`finwe/phpstan-faker`]&#40;https://github.com/finwe/phpstan-faker&#41;: PHPStan extension for Faker methods)

[comment]: <> (## License)

[comment]: <> (Faker is released under the MIT License. See the bundled LICENSE file for details.)
