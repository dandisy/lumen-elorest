<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/'], function ($router) {
    $router->post('v1/login/','UserController@authenticate');
});

$router->group(['middleware' => 'auth', 'prefix' => 'api/'], function ($router) {
    $router->post('v1/upload', function(Request $request) {
        $dir = str_replace('./','',env('SAVE_PATH')).$request->mitra_bisnis_id;
        // $dir = str_replace('./','',env('SAVE_PATH'));        
        
        if (!realpath('..'.DIRECTORY_SEPARATOR.$dir)) {
            mkdir('..'.DIRECTORY_SEPARATOR.$dir, 0777, true);
        }

        $dir = str_replace('/',DIRECTORY_SEPARATOR,$dir);
        $name = $request->mitra_bisnis_id.'_'.$request->type.'_'.$request->code.'_'.time().'.pdf';
        $path = $dir.DIRECTORY_SEPARATOR.$name;
        // $path = $dir.$name;
        $destinationPath = '..'.DIRECTORY_SEPARATOR.$dir;
        
        if($request->hasFile('file')) {
            if (realpath('..'.DIRECTORY_SEPARATOR.$path)) {
                return response(json_encode([
                    "status" => "error",
                    "message" => "file already exist"
                ], 200))
                    ->header('Content-Type', 'application/json'); 
            }

            $file = $request->file('file');
            $file->move($destinationPath, $name);
            
            if (realpath('..'.DIRECTORY_SEPARATOR.$path)) {
                return response([
                    "status" => "success",
                    "message" => "file saved successfully",
                    "data" => str_replace(DIRECTORY_SEPARATOR,'/',$path)
                ], 200)
                    ->header('Content-Type', 'application/json');
            }
        } else {
            if($request->file) {
                $data = base64_decode($request->file);
                file_put_contents(str_replace('public'.DIRECTORY_SEPARATOR,'',$path),$data);
            }
            
            // return response([
            //     "status" => "success",
            //     "message" => "file saved successfully",
            //     // "data" => str_replace('public'.DIRECTORY_SEPARATOR,'',$path)
            //     "data" => realpath('..'.DIRECTORY_SEPARATOR.$path)
            // ], 200)
            //     ->header('Content-Type', 'application/json');

            if (realpath('..'.DIRECTORY_SEPARATOR.$path)) {
                return response([
                    "status" => "success",
                    "message" => "file saved successfully",
                    "data" => str_replace(DIRECTORY_SEPARATOR,'/',str_replace('public'.DIRECTORY_SEPARATOR,'',$path))
                ], 200)
                    ->header('Content-Type', 'application/json');
            }
        }
    
        return response(json_encode([
            "status" => "error",
            "message" => "data input not valid"
        ], 200))
            ->header('Content-Type', 'application/json'); 
    });

    $router->get('v1/{model}[/{id}]', function (Request $request, $model, $id = null) {
        $paginate = null;
        $input = $request->all();
        unset($input['userid']);
        $modelNameSpace = 'App\Models\\'.$model;
        $data = new $modelNameSpace();
        if($id == 'columns') {
            return $data->getTableColumns();
        }
        if($id) {
            return $data->find($id);
        }
        if(!$input || (count($request->query()) === 1 && $request->query('userid'))) {
            return $data->get();
        }
        // foreach($input as $key => $val) {
        //     if($key === 'paginate') {
        //         $paginate = $val;
        //     }
        //     if($key !== 'page') {
        //         $vals = [];
        //         if(is_array($val)) {
        //             $vals = $val;
        //         } else {
        //             array_push($vals, $val);
        //         }
        //         foreach($vals as $item) {
        //             // if(preg_match('/\[(.*?)\]/', $item, $match)) { // due to whereIn, the $val using [...] syntax
        //             //     $item = str_replace(','.$match[0], '', $item);
        //             //     $item = explode(',', trim($item));
        //             //     array_push($item, explode(',', trim($match[1])));
        //             // } else {
        //             //     $item = explode(',', item($item));
        //             // }
        //             if($item != '') {
        //                 $item = explode(',', $item);
        //             } else {
        //                 $item = [];
        //             }
        //             $data = call_user_func_array(array($data,$key), $item);
        //             // $data = getDataQuery($data, $key, $item);//['data'];
        //         }
        //         if($key === 'paginate') {
        //             $data->appends(['paginate' => $paginate])->links();
        //         }
        //     }
        // }
        $data = getDataQuery($input, $data);
        return $data;
    });

    $router->post('v1/{model}', function(Request $request, $model) {
        $modelNameSpace = 'App\Models\\'.$model;
        $data = new $modelNameSpace();
        if($request->all()) {
            // return $data->insert($request->all());
            return $data->create($request->all());
        }
        return response(json_encode([
            "status" => "error",
            "message" => "data input not valid"
        ], 200))
            ->header('Content-Type', 'application/json');
    });

    $router->put('v1/{model}[/{id}]', function(Request $request, $model, $id = null) {
        $modelNameSpace = 'App\Models\\'.$model;
        $data = new $modelNameSpace();
        if($request->all()) {
            if($id) {
                $data = $data->find($id);
            } else {
                $data = getDataQuery($request->query(), $data);
            }
            if($data) {
                $input = array_diff($request->all(), $request->query());
                if(isset($input['userid'])) {
                    unset($input['userid']);
                }
                $data->update($input);

                return response(json_encode([
                    "status" => "success",
                    "message" => "data updated successfully"
                ], 200))
                    ->header('Content-Type', 'application/json');
            }
        }
        return response(json_encode([
            "status" => "error",
            "message" => "data input not valid"
        ], 200))
            ->header('Content-Type', 'application/json');
    });

    $router->patch('v1/{model}[/{id}]', function(Request $request, $model, $id = null) {
        $modelNameSpace = 'App\Models\\'.$model;
        $data = new $modelNameSpace();
        if($request->all()) {
            if($id) {
                $data = $data->find($id);
            } else {
                $data = getDataQuery($request->query(), $data);
            }
            if($data) {
                $data->delete();
        
                $input = array_diff($request->all(), $request->query());
                if(isset($input['userid'])) {
                    unset($input['userid']);
                }
                $input['created_by'] = $input['updated_by'];
                $data->insert($input);                

                return response(json_encode([
                    "status" => "success",
                    "message" => "data changed successfully"
                ], 200))
                    ->header('Content-Type', 'application/json');
            }
        }
        return response(json_encode([
            "status" => "error",
            "message" => "data input not valid"
        ], 200))
            ->header('Content-Type', 'application/json');
    });

    $router->delete('v1/{model}[/{id}]', function(Request $request, $model, $id = null) {
        $modelNameSpace = 'App\Models\\'.$model;
        $data = new $modelNameSpace();
        if($request->all()) {
            if($id) {
                $data = $data->find($id);
            } else {
                $data = getDataQuery($request->all(), $data)->first();
            }
            if($data) {
                return $data->delete();
            }
        }
        return response(json_encode([
            "status" => "error",
            "message" => "data input not valid"
        ], 200))
            ->header('Content-Type', 'application/json');
    });
});


/*
|--------------------------------------------------------------------------
| EloREST - Halpers
|--------------------------------------------------------------------------
|
| getDataQuery
|
*/
function getDataQuery($query, $data) {
    foreach($query as $key => $val) {
        if($key === 'paginate') {
            $paginate = $val;
        }
        if($key !== 'page') {
            $vals = [];
            if(is_array($val)) {
                $vals = $val;
            } else {
                array_push($vals, $val);
            }
            foreach($vals as $param) {
                // if(preg_match_all('/\((.*?)\)/', $request->test, $match)) { // multi occurence
                //     return $match;
                // }
                // if(preg_match('/(.*?)\((.*?)\)/', $param, $closureMatch)) { // handling closure, this only support once nested closure
                //     $param = str_replace('('.$closureMatch[2].')', '', $param);
                //     $param = explode(',', $param);
                //     foreach($param as $par) {
                //         if($par == $closureMatch[1]) {
                //             $data = $data->$key([$closureMatch[1] => function($closureQuery) use ($closureMatch) {
                //                 $closureParams = explode('=', trim($closureMatch[2]));
                //                 $closureParam = getDataQuery($closureQuery, $closureParams[0], $closureParams[1])['param'];
                //                 call_user_func_array(array($closureQuery,$closureParams[0]), $closureParam);
                //             }]);
                //         } else {
                //             $data = $data->$key($par);
                //         }
                //     }
                if(preg_match_all("/\((([^()]*|(?R))*)\)/", $param, $closureMatch)) { // handling closure, support multiple nested closure deep
                    // $closureMatch[1] = [
                    //     "contactPerson(with=phone(where=city_code,021))(where=first_name,like,%test%)",
                    //     "organization(where=name,like,%test%)",
                    //     "product"
                    // ]
                    $arrayParam = recursiveParam($param);
                    if(count($arrayParam) > 0) {
                        $data = recursiveQuery($data, $key, $param, $closureMatch, $arrayParam);//['data'];
                    }
                } else {
                    if(preg_match('/\[(.*?)\]/', $param, $arrParamMatch)) { // handling whereIn, due to whereIn params using whereIn('field', ['val_1', 'val_2', 'val_n']) syntax
                        $param = str_replace(','.$arrParamMatch[0], '', $param);
                        $param = explode(',', trim($param));
                        array_push($param, explode(',', trim($arrParamMatch[1])));
                    } else {
                        if(trim($param) != '') {
                            $param = explode(',', trim($param));
                        } else {
                            $param = [];
                        }
                    }
                    
                    $data = call_user_func_array(array($data,$key), $param);
                }
            }
            if($key === 'paginate') {
                $data->appends(['paginate' => $paginate])->links();
            }
        }
    }
    // return [
    //     'param' => $param,
    //     'data' => $data
    // ];
    return $data;
}
/*
|--------------------------------------------------------------------------
| EloREST - Halpers
|--------------------------------------------------------------------------
|
| recursiveQuery
|
*/
function recursiveQuery($data, $key, $param, $matches, $arrayParam) {
    // $arr = [
    //     "with=phone(where=city_code,021),where=city_code,021",
    //     "where=first_name,like,%test%"
    // ]
    foreach($matches[1] as $item) {
        $param = str_replace('('.$item.')', '|', $param); // signing using '|' for closure
    }
    $params = explode(',', $param);
    foreach($params as $i => $param) {
        if (strpos($param, '|')) {
            $param = rtrim($param, '|');
            $items = explode('|', $arrayParam[$i]);
            if(count($items) > 1) {
                $data = $data->$key([$param => function($query) use ($items) {
                    recursiveClosure($query, $items);
                    // this, only support second nested closure deep
                    // foreach($items as $idx => $val) {
                    //     if($idx < count($items)-1) {
                    //         $closureParam = $items[$idx+1];
                    //         $closure = str_replace('('.$closureParam.')', '', $val);
                    //         $closureData = explode('=', trim($closure));
                    //         $query = $query->$closureData[0]([$closureData[1] => function($query) use ($closureParam) {
                    //             $closureParams = explode('=', trim($closureParam));
                    //             call_user_func_array(array($query,$closureParams[0]), explode(',', trim($closureParams[1])));
                    //         }]);
                    //     }
                    // }
                }]);
            } else {
                $item = $matches[1][$i];
                $data = $data->$key([$param => function($query) use ($item) {
                    $params = explode('=', trim($item));
                    call_user_func_array(array($query,$params[0]), explode(',', trim($params[1])));
                }]);
            }
        } else {
            $data = call_user_func_array(array($data,$key), [$param]);
        }
    }
    // return [
    //     'param' => $param,
    //     'data' => $data
    // ];
    return $data;
}
/*
|--------------------------------------------------------------------------
| EloREST - Halpers
|--------------------------------------------------------------------------
|
| recursiveClosure
|
*/
function recursiveClosure($query, $items) {
    foreach($items as $idx => $val) {
        if($idx < count($items)-2) {
            $closureParam = $items[$idx+1];
            $closure = str_replace('('.$closureParam.')', '', $val);
            $closureData = explode('=', trim($closure));
            $query = $query->$closureData[0]([$closureData[1] => function($query) use ($items) {
                recursiveClosure($query, array_shift($items));
            }]);
        } else {
            if($idx < count($items)-1) {
                $closureParam = $items[$idx+1];
                $closure = str_replace('('.$closureParam.')', '', $val);
                $closureData = explode('=', trim($closure));
                $query = $query->$closureData[0]([$closureData[1] => function($query) use ($closureParam) {
                    $closureParams = explode('=', trim($closureParam));
                    call_user_func_array(array($query,$closureParams[0]), explode(',', trim($closureParams[1])));
                }]);
            }
        }
    }
}
/*
|--------------------------------------------------------------------------
| EloREST - Halpers
|--------------------------------------------------------------------------
|
| recursiveParam
|
*/
function recursiveParam($param) {
    $layer = 0;
    $arrayParam = [];
    preg_match_all("/\((([^()]*|(?R))*)\)/", $param, $matches);
    if (count($matches) > 1) {
        for ($i = 0; $i < count($matches[1]); $i++) {
            if (is_string($matches[1][$i])) {
                if (strlen($matches[1][$i]) > 0) {
                    array_push($arrayParam, $matches[1][$i]);
                    $res = recursiveParam($matches[1][$i], $layer + 1);
                    if(count($res) > 0) {
                        $arrayParam[$i] = $arrayParam[$i].'|'.$res[0];
                    }
                }
            }
        }
    } else {
        array_push($arrayParam, $param);
    }
    return $arrayParam;
}
