#PHP RAML Server
==============

Forked from https://github.com/dethbird/PHP_RAML_Server

New Features:

* multiple raml can live on server
* serving correct *.raml files with correctly set `BaseUri`
* easy integration to existing site and any framework

TBD:

* refactor out calling real implementation to remove need
  extend Controller
* refactor authorisation

Please look at clone & run example of server: ...

Here is gist with notebook examples: https://gist.github.com/jasir/1305abdeec6e259abe1fbf9a0a5c58c4


---

Original documentation here...

##Overview

This is an implementation of a Apache/PHP server which takes a [RAML](http://raml.org) API specification and stubs a working API server leveraging the [Slim Microframework](http://www.slimframework.com/).

The developer does the actual implementation of the specified API methods. Required query parameters, headers, and body elements are validated before execution of these custom methods, and a `400 Bad Request` is returned on error. It also has a way to request the response examples and schemas defined in the RAML for reflection discussed below in the usage section.

##Requirements

A web server running PHP 5.5+. You can follow [these instructions](aws-ec2-howto.md) to spin up a free Amazon EC2 with everything you need including a mysql persistence layer for the actual method implementations.


##Setup

####Make
Make sure /public is the docroot for the host

```bash
# Run composer in project root
composer install
```
####Configs

```bash
# in project root
mv configs/configs.example.yml configs/configs.yml
```

##Change the API

####RAML Specs
The RAML API definitions reside in `somedir/{version}/{api_name}.raml`

- `{version}`is determined by the version number in the api for example in http://fake-api.com/v1.1/pizza the version is "v1.1"
- `{api_name}` comes from configs and can me something like "pizza_delivery_api" or "movie_quote_reference_api". Basically by changing this, you are telling the RAML Server what API personality it should assume.

Note: Make sure that the RAML doc you define has the same version number as the folder name that it lives in:
```yml
#%RAML 0.8
title: Pizza Delivery API
version: v1.0 #be mindful that this matches the folder where it resides
```

####Method Definitions
Method Definitions live in user generated classes at `/methods/{version}{api_name|UnderscoreToUcwords}.php`.

> Example: `pizza_delivery_api` would map to `/methods/{version}/PizzaDeliveryApi.php`.

These classes must extend `MethodsBase()`. They contain methods that directly map to requests.

```php
<?php
class ExampleApi extends MethodsBase
{
    public function getExample()
    {
        $this->response->setStatus(501);
    }
}
```

Classes methods are mapped using the HTTP type and the request path:

> Example: The route `GET /v1.0/pizza/deliveries` would map to a method called `getPizzaDeliveries()` and `POST /v1.0/article/comments` would map to `postArticleComments()`

As a general rule, while stubbing these out, you should set the response status to `501` to indicate that it is not implemented:
```php
    public function postArticleComments ()
    {
        $this->response->setStatus(501);
    }
```

At this point the implementation is up to you. You could spawn a process in another language such as Python, or even call another service entirely! It's a real magical world.

##Hit The API!

####Basic Usage
Assuming your web server is running, we can now hit the endpoints of the API we have assumed. We can use a tool like [Postman](https://www.getpostman.com/) to make these requests. You could also just use command line `curl`.

Required query parameters, headers, and body schemas will be respected and `HTTP 400 Bad Request` will be returned for all malformed requests.

**cURL**

here are some example calls in curl for POST /correction:

```bash
# 201 example
curl -X POST -H "X-Http-Example: 201"  http://54.148.30.160/v1.0/correction 
# 201 schema
curl -X POST -H "X-Http-Example: 201" -H "X-Http-Schema: 1"  http://54.148.30.160/v1.0/correction
# missing release id required query param
curl -X POST -H "User-Id: 1234” -H "Vendor-Id: 5678"  http://54.148.30.160/v1.0/correction
# successful post (501 not implemented)
curl -X POST -H "User-Id: 1234” -H "Vendor-Id: 5678"  http://54.148.30.160/v1.0/correction?release_id=9988776 
```

####Fetching Response Examples and Schemas

Two reserved HTTP headers exist for bypassing validation and just returning the examples and schemas defined in the RAML. Each response code 200, 201, 202 example and schema is requested one at a time.

`X-Http-Example`: HTTP Status Code (200,201,202,...)

`X-Http-Schema`: 1|null - if this flag is set it will return the schema for the requested X-Http-Example HTTP code.


