<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

return function (App $app) {
    
    $container = $app->getContainer();
    
    $app->get('/', function(Request $request, Response $response) {
        echo "hello first page";
    });


    // Login และ รับ Token
    $app->post('/login', function (Request $request, Response $response, array $args) use ($container){
 
        $input = $request->getParsedBody();
        $username = $input['username'];
        $password = sha1($input['password']);

        $sql = "SELECT * FROM users WHERE username=:username and password=:password";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("username", $username);
        $sth->bindParam("password", $password);
        $sth->execute();

        $count = $sth->rowCount();
        if($count){
            $user = $sth->fetchObject();
            $settings = $this->get('settings'); // get settings array.
            $token = JWT::encode(['id' => $user->id, 'username' => $user->username, 'fullname' => $user->fullname ], $settings['jwt']['secret'], "HS256");
            return $this->response->withJson(['token' => $token]);
        }else{
            return $this->response->withJson(['error' => true, 'message' => 'These credentials do not match our records.']);
        }
    });



    $app->group('/api', function() use ($app) {

        $container = $app->getContainer();
        
        $app->get('/products', function(Request $request, Response $response, array $args) use ($container) {
            
            $user = $request->getAttribute("decoded_token_data");

            $sql = "SELECT * FROM products ORDER BY id desc";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll();

            if(count($products)){
                $res = [
                    'user' => $user,
                    'status' => 'success',
                    'message' => 'Read Product Success',
                    'data' => $products
                ];
            }else{
                $res = [
                    'status' => 'fail',
                    'message' => 'Empty Product Data',
                    'data' => $products
                ];
            }

            return $this->response->withJson($res);
        });
    
        $app->get('/product/{id}', function(Request $request, Response $response, array $args) use ($container) {
            $sql = "SELECT * FROM products WHERE id='{$args['id']}'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll();

            if(count($products)){
                $res = [
                    'status' => 'success',
                    'message' => 'Read Product Success',
                    'data' => $products[0]
                ];
            }else{
                $res = [
                    'status' => 'fail',
                    'message' => 'Empty Product Data',
                    'data' => $products[0]
                ];
            }

            return $this->response->withJson($res);
        });

        $app->post('/products', function(Request $request, Response $response, array $args) use ($container) {
            $body = $this->request->getParsedBody();
            $img = "noimg.jpg";
             $sql = "INSERT INTO products(product_name,product_detail,product_barcode,product_price,product_qty,product_image) 
                        VALUES(:product_name,:product_detail,:product_barcode,:product_price,:product_qty,:product_image)";
            $sth = $this->db->prepare($sql);
            $sth->bindParam("product_name", $body['product_name']);
            $sth->bindParam("product_detail", $body['product_detail']);
            $sth->bindParam("product_barcode", $body['product_barcode']);
            $sth->bindParam("product_price", $body['product_price']);
            $sth->bindParam("product_qty", $body['product_qty']);
            $sth->bindParam("product_image", $img);

            if($sth->execute()){
                $data = $this->db->lastInsertId();
                $input = [
                    'id' => $data,
                    'status' => 'success'
                ];
            }else{
                $input = [
                    'id' => '',
                    'status' => 'fail'
                ];
            }

            return $this->response->withJson($input); 

        });

        $app->put('/product/{id}', function(Request $request, Response $response, array $args) use ($container) {
            $body = $this->request->getParsedBody();

            $sql = "UPDATE  products SET 
                           product_name=:product_name,
                           product_detail=:product_detail,
                           product_barcode=:product_barcode,
                           product_price=:product_price,
                           product_qty=:product_qty
                       WHERE id='$args[id]'";

           $sth = $this->db->prepare($sql);
           $sth->bindParam("product_name", $body['product_name']);
           $sth->bindParam("product_detail", $body['product_detail']);
           $sth->bindParam("product_barcode", $body['product_barcode']);
           $sth->bindParam("product_price", $body['product_price']);
           $sth->bindParam("product_qty", $body['product_qty']);
           

           if($sth->execute()){
               $data = $args['id'];
               $input = [
                   'id' => $data,
                   'status' => 'success'
               ];
           }else{
               $input = [
                   'id' => '',
                   'status' => 'fail'
               ];
           }

           return $this->response->withJson($input);  

        });

        $app->delete('/product/{id}', function(Request $request, Response $response, array $args) use ($container) {
            $body = $this->request->getParsedBody();

            $sql = "DELETE FROM products WHERE id='$args[id]'";

           $sth = $this->db->prepare($sql);
           if($sth->execute()){
               $data = $args['id'];
               $input = [
                   'id' => $data,
                   'status' => 'success'
               ];
           }else{
               $input = [
                   'id' => '',
                   'status' => 'fail'
               ];
           }

           return $this->response->withJson($input);  

        });
    
    });
};
