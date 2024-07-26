<?php

namespace deemru;

require_once __DIR__ . '/Triples.php';

class KV
{
    public $db;
    private $kv;
    private $vk;
    private $hits;
    private $ki;
    private $vi;
    private $recs;
    private $r;
    private $w;
    private $high;

    public function __construct( $bidirectional = false, $hits = false )
    {
        $this->kv = [];
        if( $bidirectional )
            $this->vk = [];
        if( $hits )
            $this->hits = [];
    }

    public function __destruct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->merge();

        $this->kv = [];
        if( isset( $this->vk ) )
            $this->vk = [];
        if( isset( $this->hits ) )
            $this->hits = [];
    }

    public function setStorage( $db, $name, $writable, $keyType = 'INTEGER PRIMARY KEY', $valueType = 'TEXT UNIQUE' )
    {
        $this->db = new Triples( $db, $name, $writable, [ $keyType, $valueType ] );
        $this->ki = false !== strpos( $keyType, 'INTEGER' );
        $this->vi = false !== strpos( $valueType, 'INTEGER' );

        if( $writable )
            $this->recs = [];

        return $this;
    }

    public function setValueAdapter( $r, $w )
    {
        $this->r = $r;
        $this->w = $w;
        return $this;
    }

    public function setHigh()
    {
        if( false === ( $this->high = $this->db->getHigh( 0 ) ) )
            $this->high = 0;
        return $this->high;
    }

    public function merge()
    {
        if( isset( $this->recs ) && count( $this->recs ) )
        {
            if( isset( $this->w ) )
                foreach( $this->recs as $key => $value )
                    $this->recs[$key] = $this->w->__invoke( $value );
            $this->db->merge( $this->recs, true );
            $this->recs = [];
        }
    }

    public function setKeyValue( $key, $value )
    {
        if( isset( $this->vk ) )
        {
            if( isset( $this->kv[$key] ) )
                $this->vk[$this->kv[$key]] = false;
            $this->vk[$value] = $key;
        }

        $this->kv[$key] = $value;

        if( isset( $this->hits ) )
            $this->hits[$key] = 1;

        if( isset( $this->recs ) )
            $this->recs[$key] = $value;

        return $key;
    }

    public function getKeyByValue( $value )
    {
        if( isset( $this->vk[$value] ) )
        {
            $key = $this->vk[$value];
            if( isset( $this->hits ) )
                ++$this->hits[$key];
            return $key;
        }
        else
        if( !isset( $this->vk ) )
            return false;

        if( isset( $this->db ) )
        {
            $key = $this->db->getUno( 1, $value );
            if( $key !== false )
                $key = $this->ki ? (int)$key[0] : $key[0];
        }
        else
            $key = false;

        $this->kv[$key] = $value;
        $this->vk[$value] = $key;
        if( isset( $this->hits ) )
            $this->hits[$key] = 1;

        return $key;
    }

    public function getValueByKey( $key )
    {
        if( isset( $this->kv[$key] ) )
        {
            if( isset( $this->hits ) )
                ++$this->hits[$key];
            return $this->kv[$key];
        }

        if( isset( $this->db ) )
        {
            $value = $this->db->getUno( 0, $key );
            if( $value !== false )
            {
                $value = $value[1];
                if( isset( $this->r ) )
                    $value = $this->r->__invoke( $value );
                else if( $this->vi )
                    $value = (int)$value;
            }
        }
        else
            $value = false;

        $this->kv[$key] = $value;
        if( isset( $this->vk ) )
            $this->vk[$value] = $key;
        if( isset( $this->hits ) )
            $this->hits[$key] = 1;

        return $value;
    }

    public function getForcedKeyByValue( $value )
    {
        $key = $this->getKeyByValue( $value );
        if( $key !== false )
            return $key;

        if( !isset( $this->high ) )
            $this->setHigh();

        return $this->setKeyValue( ++$this->high, $value );
    }

    public function cacheHalving()
    {
        $this->merge();
        $half = count( $this->kv ) >> 1;

        if( isset( $this->hits ) )
        {
            arsort( $this->hits );
            $this->hits = array_slice( $this->hits, 0, $half, true );
            $kv = [];
            if( isset( $this->vk ) )
                $this->vk = [];
            foreach( $this->hits as $key => $weight )
            {
                $this->hits[$key] = $weight >> 1;
                $kv[$key] = $value = $this->kv[$key];
                if( isset( $this->vk ) )
                    $this->vk[$value] = $key;
            }

            $this->kv = $kv;
        }
        else
        {
            shuffle( $this->kv );
            $this->kv = array_slice( $this->kv, 0, $half, true );
            if( isset( $this->vk ) )
            {
                $this->vk = [];
                foreach( $this->kv as $key => $value )
                    $this->vk[$value] = $key;
            }
        }
    }
}
