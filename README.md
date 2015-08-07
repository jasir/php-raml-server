#RAML Server
==============

##Overview
==============

This is an implementation of a Apache/PHP server which takes a RAML API specification and stubs a working server. Left to the developer is the actual implementation of the specified methods. Required query parameters, headers, and body elements are validated before execution. It also has a way to request response examples and schemas.

##Requirements
==============

A web server running PHP 5.5+. You can follow [these instructions](aws_ec2_setup.md) to spin up a free Amazon EC2 with everything you need including a mysql persistence layer for the actual method implementations.


##Setup
==============

####Make
Make sure /public is the docroot

```bash
# Run composer in project root
composer install
```
####Configs

```bash
# in project root
mv configs/configs.example.yml configs/configs.yml
```
####RAML Specs
The raml spec files reside in `/public/raml/{version}/{raml_spec_filename}`

- `{version}`is determined by the version number in the api for example in http://fake-api.com/v1.1/pizza the version is "v1.1"
- `{raml_spec_filename}` comes from configs and can me something like "pizza_delivery_api.raml" or "movie_quote_reference_api.raml". Since it is using file_get_contents internally, this could technically be a remote URL. Basically by changing this, you are telling the RAML Server what API personality it should assume and act as.

```bash
# change the raml_spec_filename` if desired
# raml_spec_filename: "example.raml"
vim configs/configs.yml
```

##Usage
==============

####Basic Usage
We can now hit the endpoints of the API we have assumed the personality of by setting `raml_spec_filename` in the configs by using a tool like [Postman](https://www.getpostman.com/).

Required query parameters, headers, and body schemas will be respected and will return 400 for malformed requests.

Please see the next section about implementing the guts of the API methods.

##Development
==============
We now have to get to the meat and potatoes of the methods. Methods are all implemented in `/src/Library/Route/Methods.php` and the method names are mapped as such:

`{http type (get|post|patch|...}_{method_001}`...`_{method_n}`

Example:

- GET http://fake-example/v1.0/pizza/deliveries becomes `get_pizza_deliveries`
- POST http://fake-example/v1.0/quote/source becomes `post_quote_source`

It is up to you to extend and modify the Methods.php class as you see fit. It should really just be the basic guts of what the method's primary function is. Like, connect to the database and retrieve the list of deliveries for a given order id.

##API Testing
Abao
X-Http-Example, X-Http-Schema


