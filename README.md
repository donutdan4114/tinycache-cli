# TinyCache CLI
A simple CLI for interacting with the [tinycache.io](https://tinycache.io) API.

## Setup
The application can be downloaded from `/bin/tinycache.phar`. [Click here to download](https://github.com/donutdan4114/tinycache-cli/blob/master/bin/tinycache.phar?raw=true).  
You can also download the application and make modifications, and rebuild using [Box](http://box-project.org).

## Usage
To use the CLI, you must get an API key from [https://tinycache.io](https://tinycache.io)  
You should set your API key as a global environment variable:  
`export TINYCACHE_API_KEY=your_api_key`  

Run `tinycache get` to ensure you get a response:  
`{"keys":["test","test1"],"requests_today":"2","requests_remaining":998}`

View the help documentation for more info about various arguments/options.  
```
> tinycache --help
HTTP Method
     Required. Either GET, POST, PUT, or DELETE

Cache Key
     Cache key to interact with.

-d/--data <argument>
     Data to send as params.

-e/--encrypt/--decrypt <argument>
     Encryption/Decryption key.

-x/--expire <argument>
     Expire time to set in POST or PUT.

-f/--file <argument>
     File to pass as data. Will be base64 encoded.

-j/--json <argument>
     Raw JSON to send to the API.

-q/--query <argument>
     Query params for doing an advanced cache query.

-v/--value <argument>
     Cache value to POST or PUT.
```

### GET
View cache values from the API.
```
> tinycache get
{"keys":["test","test1"],"requests_today":"2","requests_remaining":998}

> tinycache get test
[{"cache_key":"test","cache_value":"test","encrypted":"0","expire":"0"}]

> tinycache get --query "p[value][contains]=test"
[{"cache_key":"test","cache_value":"test","encrypted":"0","expire":"0"},{"cache_key":"test1","cache_value":"test","encrypted":"0","expire":"1432746087"},{"cache_key":"test2","cache_value":"test","encrypted":"0","expire":"1432742517"},{"cache_key":"test3","cache_value":"test","encrypted":"0","expire":"1432742531"},{"cache_key":"test4","cache_value":"test","encrypted":"0","expire":"1432742546"}]

> tinycache get test4 --decrypt "my secure string"
[{"cache_key":"test4","cache_value":"mysecret","encrypted":"1","expire":"1432747695"}]
```

### POST
Create new cache values.
```
> tinycache post test3 -v potato
{"saved":"true"}

> tinycache post test3 --value potato
{"saved":"true"}

> tinycache post test4 -v mysecret --encrypt "my secure string"
{"saved":"true"}

> tinycache post test5 --data 'cache_value=test&expire=3600'
{"saved":"true"}

> tinycache post test6 --json '{"cache_value":"something","expire":3600}'
{"saved":"true"}

> tinycache post test7 --file /some/file/path.txt
{"saved":"true"}
```

### PUT
Update existing cache values through the API.
```
> tinycache put test3 --value apple
{"saved":"true"}

> tinycache put test123 -v doesnotexist
{"type":"error","code":404,"status":"Not Found","message":"Invalid key"}
```

### DELETE
Deletes keys from the cache store.
```
> tinycache delete test3
{"deleted":1}

> tinycache delete "tes*"
{"deleted":5}

> tinycache delete --query "p[value][contains]=test"
{"deleted":3}
```
