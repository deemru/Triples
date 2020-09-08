<?php

namespace deemru;

require_once __DIR__ . '/Triples.php';

class KV
{
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
    }

    public function merge()
    {
        $merged = true;
        if( isset( $this->recs ) && count( $this->recs ) )
        {
            if( isset( $this->w ) )
                foreach( $this->recs as $key => $value )
                    $this->recs[$key] = $this->w->__invoke( $value );
            $merged = $this->db->merge( $this->recs, true );
            $this->recs = [];
        }

        return $merged;
    }

    public function setKeyValue( $key, $value )
    {
        if( isset( $this->vk ) && isset( $this->kv[$key] ) )
            $this->vk[$this->kv[$key]] = false;

        $this->kv[$key] = $value;
        if( isset( $this->vk ) )
            $this->vk[$value] = $key;
        if( isset( $this->hits ) )
            $this->hits[$key] = 1;

        if( isset( $this->recs ) )
            $this->recs[$key] = $value;

        return $key;
    }

    public function getKeyByValue( $value )
    {
        assert( isset( $this->vk ) );

        if( isset( $this->vk[$value] ) )
        {
            $key = $this->vk[$value];
            if( isset( $this->hits ) )
                ++$this->hits[$key];
            return $key;
        }

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
        assert( isset( $this->hits ) );

        $this->merge();

        $kv = [];
        if( isset( $this->vk ) )
            $vk = [];
        $hits = [];

        arsort( $this->hits );
        $invalid = count( $this->hits ) >> 1;
        $i = 0;
        foreach( $this->hits as $key => $num )
        {
            if( ++$i > $invalid )
                break;

            $value = $this->kv[$key];
            $kv[$key] = $value;
            if( isset( $vk ) )
                $vk[$value] = $key;
            $hits[$key] = $num >> 1;
        }

        $this->kv = $kv;
        if( isset( $this->vk ) )
            $this->vk = $vk;
        $this->hits = $hits;
    }
}
