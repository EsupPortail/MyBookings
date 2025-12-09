<?php

namespace App\Service;

use Random\RandomException;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Ldap as LdapSymfo;

class Ldap
{
    private array $mapping;
    /**
     * @param $ldapParameters
     */
    public function __construct(private $ldapParameters)
    {
        $this->mapping = $this->ldapParameters['field_mapping'];
    }

    /**
     * @return LdapSymfo
     */
    public function connect(): LdapSymfo
    {
        $ldap = LdapSymfo::create('ext_ldap',['connection_string'=>$this->ldapParameters["connection_string"]]);
        $ldap->bind($this->ldapParameters["dn"],$this->ldapParameters["password"]);
        return $ldap;
    }

    public function sendQuery($query):array
    {
        $ldap = $this->connect();
        return $ldap->query($this->ldapParameters["base_dn"], $query)->execute()->toArray();
    }

    public function getData(Entry $ldapUser,array $fields) : array|string|null
    {
        $return=[];
        foreach ($fields as $field)
        {
            if (!isset($this->mapping[$field]))
                continue;

            $mappedField = $this->mapping[$field];
            $value=null;
            if ($ldapUser->hasAttribute($mappedField))
            {
                if (count($ldapUser->getAttribute($mappedField)) === 1)
                    $value = current($ldapUser->getAttribute($mappedField));
                else
                    $value = $ldapUser->getAttribute($mappedField);
            }

            if (count($fields) === 1)
                return $value;

            $return[$field] = $value;
        }

        return $return;
    }

    /**
     * @param $username
     * @return string
     */
    public function getBadgeNumber($username) : string|null
    {
        if($ldapUser = $this->findLdapUser($username))
            return $this->getData(current($ldapUser),["badgenumber"]);

        return '';
    }

    public function getLdapUserVdipInformations(string $arg) : array|null
    {
        if($userLdap = $this->findLdapUser($arg))
        {
            $userData = $this->getData(current($userLdap),['firstname','lastname','email','civility','username','badgenumber']);

            return [
                'first_name' => $userData["firstname"],
                'name' => $userData["lastname"],
                'email' => $userData["email"],
                'uid_civility' => $userData["civility"],
                'number' => $userData["username"],
                'clfdcsn' => $userData["badgenumber"],
                'uid_company' => 1
            ];
        }
        return null;
    }

    /**
     * @throws RandomException
     */
    public function getAllLdapUsers(string $arg, string $searchExtraFilter="search_all_extra_filter") : array
    {
        $givenName = "";
        $sn = "";
        $realName=false;
        $articles = ["le", "la", "les", "du", "de", "des", "d'", "del", "de los", "de la", "di", "de", "d'", "del", "della", "dei", "degli", "delle", "do", "dos", "da", "das", "von", "zu", "van", "van de", "van der", "ten", "o'", "mac", "al", "bin", "af", "ben", "bat", "el"];
        foreach (explode(" ",$arg) as $string)
        {
            if ($realName === true)
            {
                $givenName .= $string;
                continue;
            }

            if (in_array($string,$articles))
                $string .= " ";
            else
                $realName = true;

            $sn .= $string;
        }

        $query = "(&".$this->ldapParameters[$searchExtraFilter]."(&(".$this->mapping["lastname"]."=$sn*)(".$this->mapping["firstname"]."=$givenName*)))";
        $users = $this->sendQuery($query);

        $userList=[];
        foreach ($users as $user)
        {
            $userData = $this->getData($user,["username","displayname","firstname","lastname","email"]);
            $ldapUser = [
                'uid' => $userData["username"],
                'label' => $userData["lastname"]." ".$userData["firstname"],
                'displayName' => $userData["displayname"],
                'mail' => $userData["email"],
            ];

            $key = $userData["lastname"]."   ".$userData["firstname"];
            if (array_key_exists($key,$userList))
            {
                if(isset($userList[$key]['mail'])) {
                    $userList[$key]['label'] .= " (".$userList[$key]['mail'].")";
                    unset($userList[$key]['mail']);
                }
                $ldapUser["label"] .= " (".$userData["email"].")";
                $key .= random_bytes(8);
            }

            $userList[$key] = $ldapUser;
        }

        foreach ($userList as $key => $user) {
            if(isset($userList[$key]['mail'])) {
                unset($userList[$key]['mail']);
            }
        }

        ksort($userList);
        return array_values($userList);
    }

    public function getAllUsersFromQuery(string $query):array
    {
        $users = $this->sendQuery($query);
        $arrayUsers=[];
        foreach ($users as $user)
        {
            $arrayUsers[] = $this->getData($user,['username']);
        }
        return $arrayUsers;
    }

    public function findLdapUser($username)
    {
        return $this->sendQuery("(&(".$this->mapping["username"]."=".$username."))");
    }
}
