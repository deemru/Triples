<?php

require __DIR__ . '/../vendor/autoload.php';
use deemru\Triples;

$dbpath = __DIR__ . '/triples.sqlite';
$triples = new Triples( $dbpath, 'triples', true, [ 'INTEGER PRIMARY KEY', 'TEXT UNIQUE', 'INTEGER' ], [ 0, 0, 1 ] );

$r0 = 1;
$r1 = 'Hello, World!';
$r2 = crc32( $r1 );
$rec = [ $r0, $r1, $r2 ];
$recs = [ $rec ];
$triples->merge( $recs );

if( $triples->getUno( 2, $r2 )[0] != $r0 ||
    $triples->getUno( 1, $r1 )[2] != $r2 ||
    $triples->getUno( 0, $r0 )[1] !== $r1 )
    exit( 1 );

if( !$triples->query( 'DELETE FROM ' . $triples->name ) ||
    $triples->getUno( 2, $r2 ) !== false ||
    $triples->getUno( 1, $r1 ) !== false ||
    $triples->getUno( 0, $r0 ) !== false )
    exit( 1 );

class tester
{
    private $successful = 0;
    private $failed = 0;
    private $depth = 0;
    private $info = [];
    private $start = [];

    public function pretest( $info )
    {
        $this->info[$this->depth] = $info;
        $this->start[$this->depth] = microtime( true );
        if( !isset( $this->init ) )
            $this->init = $this->start[$this->depth];
        $this->depth++;
    }

    private function ms( $start )
    {
        $ms = ( microtime( true ) - $start ) * 1000;
        $ms = $ms > 100 ? round( $ms ) : $ms;
        $ms = sprintf( $ms > 10 ? ( $ms > 100 ? '%.00f' : '%.01f' ) : '%.02f', $ms );
        return $ms;
    }

    public function test( $cond )
    {
        $this->depth--;
        $ms = $this->ms( $this->start[$this->depth] );
        echo ( $cond ? 'SUCCESS: ' : 'ERROR:   ' ) . "{$this->info[$this->depth]} ($ms ms)\n";
        $cond ? $this->successful++ : $this->failed++;
    }

    public function finish()
    {
        $total = $this->successful + $this->failed;
        $ms = $this->ms( $this->init );
        echo "  TOTAL: {$this->successful}/$total ($ms ms)\n";
        sleep( 3 );

        if( $this->failed > 0 )
            exit( 1 );
    }
}

echo "   TEST: Pairs\n";
$t = new tester();

for( $iters = 50000; $iters >= 100; $iters = (int)( $iters / 10 ) )
{
    $data = [];
    $t->pretest( "fill PHP data ($iters)" );
    {
        for( $i = 0; $i < $iters; $i++ )
        {
            $r0 = $i;
            $r1 = sha1( $i );
            $r2 = crc32( $r1 );
            $data[] = [ $r0, $r1, $r2 ];
        }
        
        $t->test( count( $data ) === $iters );
    }

    $triples->query( 'DELETE FROM ' . $triples->name );

    $t->pretest( "data to Pairs ($iters) (write) (commit)" );
    {
        $triples->begin();
        $result = $triples->merge( $data );
        $triples->commit();
    }
    $t->test( $result !== false );
    $t->pretest( "data to Pairs ($iters) (read)  (commit)" );
    {
        foreach( $data as $r )
        {
            if( $triples->getUno( 1, $r[1] )[2] != $r[2] ||
                $triples->getUno( 0, $r[0] )[1] !== $r[1] )
            {
                $result = false;
                break;
            }
        }

        $t->test( $result !== false );
    }

    $triples->query( 'DELETE FROM ' . $triples->name );

    $t->pretest( "data to Pairs ($iters) (write) (merge)" );
    {
        $result = $triples->merge( $data );
    }
    $t->test( $result !== false );
    $t->pretest( "data to Pairs ($iters) (read)  (merge)" );
    {
        foreach( $data as $r )
        {
            if( $triples->getUno( 1, $r[1] )[2] != $r[2] ||
                $triples->getUno( 0, $r[0] )[1] !== $r[1] )
            {
                $result = false;
                break;
            }
        }

        $t->test( $result !== false );
    }
}

$t->finish();
