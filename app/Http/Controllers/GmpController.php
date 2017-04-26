<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contact;
class GmpController extends Controller
{
    //

    /**
     *
     */


    public function index(){

        $arrGroupID = array(['579048','NEW'],
            ['579466','RESPONSE'],
            ['579531','WARM'],
            ['579657','DEAL']);

        foreach($arrGroupID as $data){
            $contacts =  $this->gmp_api($data[0]);
            $results = $contacts[0]["Results"];
            if(is_array($results))
                $this->gmp_dbase($results,$data[1]);
        }
    }

    public function hswebhook(){
            //$testJson = '[{"objectId":8501,"propertyName":"hs_lead_status","propertyValue":"DEAL","changeSource":"API","eventId":4083751833,"subscriptionId":3167,"portalId":3088964,"appId":39543,"occurredAt":1493051724096,"subscriptionType":"contact.propertyChange","attemptNumber":0}]';


            $json = file_get_contents('php://input');

        $file = fopen("test.txt","a+");
        echo fwrite($file,$json);
        fclose($file);
        echo "Success";

            $datahs = json_decode($json,true);
            $objectId = $datahs[0]["objectId"];
            $propertyName = $datahs[0]["propertyName"];
            $propertyValue = $datahs[0]["propertyValue"];
            $subscriptionType = $datahs[0]["subscriptionType"];


            $contact = Contact::where("vid",$objectId)->first();
            switch($subscriptionType){
                case "contact.propertyChange":
                    $arrGroupID = array(['579048','NEW'],
                        ['579466','RESPONSE'],
                        ['579531','WARM'],
                        ['579657','DEAL']);
                    $groupID = "";
                    foreach($arrGroupID as $data){
                        if($data[1] == $propertyValue){
                            $groupID = $data[0];
                        }
                    }
                    $contact->GroupId = $groupID;
                    $contact->save();
                    $this->update_gmp($groupID,$contact->LeadId,'groupId');
                    break;

                case "contact.creation":

                    break;

            }



            //var_dump($datahs);

    }

    public function update_gmp($groupID,$userId,$fieldname){


        $url = 'https://rightonprofit.net/glu/webservice/';
        $myemail = "admin@directcapitalsource.info";
        $mypass = "Capital1";
        $fields = array('userEmail'=>$myemail,'UserPwd'=>$mypass,'UserField'=>$fieldname,'UserData'=>$groupID,'userId'=>$userId,'svr'=>'UpdateExistingUser');
        //$fields = array('userEmail'=>$myemail,'UserPwd'=>$mypass,'UserField'=>'GroupId','UserData'=>$groupID,'userId'=>$userId,'svr'=>'UpdateExistingUser');
        $fields=json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, array("Data"=>$fields));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $contacts = json_decode($result, true);
        var_dump($contacts);

    }

    public function gmp_dbase($results,$status){
        foreach ($results as  $contacts) {
            $contact =  new Contact;
            foreach($contacts as $key => $value){
                $isexist = Contact::checkfield($key);
                $contact->$key = $value;
            }
            if($contact->gotDuplicates("LeadId")){
                echo "Have Duplicates CHECK HS";
                $contact2 = Contact::where("LeadId",$contacts["LeadId"])->first();
                $this->update_hs($contact2,$status);
            }else{
                $this->hs_api($contacts,$status);
                echo "New Record Found Add now in HS";
            }
        }
    }

    public function update_hs($response,$status){
            $hapikey = "2b8a991b-a9d9-40ef-a29e-29ede6703c4c";
            $vid = $response["vid"];
            $dataInput = [];
            array_push($dataInput, array('property' => "hs_lead_status",'value' => $status));
            $arrInputContact = array('properties' => $dataInput);
            $post_json = json_encode($arrInputContact);
            $endpoint = 'https://api.hubapi.com/contacts/v1/contact/vid/'.$vid.'/profile?hapikey=' . $hapikey;
            $ch = @curl_init();
            @curl_setopt($ch, CURLOPT_POST, true);
            @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
            @curl_setopt($ch, CURLOPT_URL, $endpoint);
            @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = @curl_exec($ch);
            $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errors = curl_error($ch);
            @curl_close($ch);
            echo "curl Errors: " . $curl_errors;
            echo "\nStatus code: " . $status_code;
            echo "\nResponse: " . $response;
            echo "testset";
    }


    public function gmp_api($groupID){
        $url = 'https://rightonprofit.net/glu/webservice/';
        $myemail = "admin@directcapitalsource.info";
        $mypass = "Capital1";
        $fields = array('userEmail'=>$myemail,'UserPwd'=>$mypass,'searchType'=>'groupId','groupId'=>$groupID,'svr'=>'SearhByUserInput');
        $fields=json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST,count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, array("Data"=>$fields));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $contacts = json_decode($result, true);
        return $contacts;
    }

    //Hubspot Section
    /**
     * Copyright 2011 HubSpot, Inc.
     *
     *   Licensed under the Apache License, Version 2.0 (the
     * "License"); you may not use this file except in compliance
     * with the License.
     *   You may obtain a copy of the License at
     *
     *       http://www.apache.org/licenses/LICENSE-2.0
     *
     *   Unless required by applicable law or agreed to in writing,
     * software distributed under the License is distributed on an
     * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
     * either express or implied.  See the License for the specific
     * language governing permissions and limitations under the
     * License.
     */

    function hs_api($value1,$status){

        $dataInput = [];
        $arrHBvalidInput = array('email','firstname','lastname','phone');
        foreach($value1 as $key=>$value){
            $mykey = str_replace('-', '', strtolower($key));

            if(in_array($mykey, $arrHBvalidInput)){
              //  echo $mykey." MY KEY ".$value."<br/>";
                if($value != ""){
                    array_push($dataInput, array('property' => $mykey,'value' => $value));
                }

            }
        }
        array_push($dataInput, array('property' => "hs_lead_status",'value' => $status));
        $arrInputContact = array('properties' => $dataInput);
        $post_json = json_encode($arrInputContact);
        $hapikey = "2b8a991b-a9d9-40ef-a29e-29ede6703c4c";
        $endpoint = 'https://api.hubapi.com/contacts/v1/contact?hapikey=' . $hapikey;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
        echo "curl Errors: " . $curl_errors;
        echo "\nStatus code: " . $status_code;
        echo "\nResponse: " . $response;
        echo "testset";

        $results = json_decode($response, true);
        $results3 = array_merge($results,$value1);
        // $this->hs_dbase($results3);
        $contact =  new Contact;
        foreach ($results3 as  $key=>$value) {
            $isexist = Contact::checkfield($key);
            if(!is_array($value))
                $contact->$key = $value;

        }
        $contact->gotDuplicates("LeadId");

//        $contact =  new Contact;
//        if($contact->gotDuplicates("LeadId")){
//            echo "Have Duplicates CHECK HS";
//        }else{
//            // $this->hs_api($contacts );
//            echo "New Record Found Add now in HS";
//        }

      //  var_dump($value1);
      //  $this->hs_dbase($results);
    }
}
