### Don't write that `switch` again.
Are you tired of writing the same Ajax handler code over and over again on your each project? There are always the same steps and same problems, is it handling headers correctly? Will it work with jQuery? Status Codes? JSONP? 

Well, I've collected all the code pieces together and created the AjaxHanler. It's a very simple PHP Class which you can inherit and create your own Ajax handlers easily.

### Features
Here is a list of features Ajax Handler provides

*   __Unified Output__ Your every Ajax request will produce a standard JSON output, so your client code will be simpler and understandable. Outputs are only handled by ::error and ::success methods, You'll never have to use `json_encode` when outputting.
*   __Error Handling__ AjaxHandler will automatically catch exceptions and PHP errors and handle them for you. You can customize error messages and changes status codes
*   __JSONP Support__ Ajax handler will automatically handle requests with callback parameters, you can customize the callback name and type
*   __Clean Code__ Object Oriented design will let you easily tear apart your code into separate files, This is especially good for Backbone like MVC projects
*   __Stats__ Ajax handler will keep duration of your each request and return it to you, so you can easily measure your codes performance
*   __Very Simple Code__ Ajax Handler's code is very simple and you can easily customize it according to your needs.
*   __It's All Free__ Ajax Handler is an MIT licensed piece of code so you can use it own your own projects


Here is a very basic example of AjaxHandler in Use lets say this is person.php

```php
<?php
include "AjaxHandler.php";
class Person extends AjaxHandler{
    
    /**
     * Private function will not be accessed from outside
     * @return Mongo Collection
     */
    private function getMongo(){
        $m = new Mongo();
        $db = $m->selectDB('main');
        $cl = $db->selectCollection('people');
        return $cl;
    }

    /**
     * All I write here is the code
     * I didn't write anything to handle output
     * If the code gives an exception, ajax 
     * handler will return error, If Everything is successfull
     * Standart success message will be returned
     * @return [type] [description]
     */
    public function createPerson(){
        
        $db = $this->getMongo();
        
        $result = $cl->save(array(
            "name" => $this->get('name'),
            "age"  => $this->get('age')
        ));
    }
    
    /**
     * Here is the code for handling your own messages
     * @return [type] [description]
     */
    public function getPersonDetails(){
        $db = $this->getMongo();

        $cursor = $db->fetch(array('name' => $this->get('name')));

        if($cursor->count() === 0){
            // Will produce an error
            // {"success": false, "error": "Person cannot be found"}
            $this->error('Person cannot be found');
        }else{
            // Will giveout a JSON success message
            // {
            //   "success": true, 
            //   "details":{"name":"john", "age":"29"}, 
            //   "message":"Operation successful"
            // }
            $this->success(array(
                "details"=>$cursor->first()
            ));
        }
    }
}
?>
```

You can make requests with jQuery easily

```javascript
$.ajax({
    url:'person.php',
    data:{
      action: 'createPerson',
      name: 'john',
      age: 29
    },
    dataType:'json',
    complete: function(res){
        if(res.success){
            alert('User Saved');
        }else{
            alert('Error: '+ res.error);
        }
    }
});
```
I'll write a more detailed documentation soon.