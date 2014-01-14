<?php
/**
 * FoxProReaderTest class
 */

/**
 * FoxPro DBF file reader, read data from *.dbf and *.fpt from DOS version of FoxPro.
 * Doesn't work for Visual FoxPro files.
 *
 * Based on dbf_class by by Faro K Rasyid (Orca) orca75_at_dotgeek_dot_org
 * and Nicholas Vrtis vrtis_at_vrtisworks_dot_com
 *
 * NOTE: Class read both files (dbf and fpt) into memory, so this could
 * take a lot of memory for large files.
 *
 * @author Zdenek Gebauer <zdenek.gebauer@gmail.com>
 */
class FoxProReader
{
    /** @var array info about columns, each item is associative array with keys ['name'],['len'],['type'] */
    private $_cols;
    /** @var int number of records */
    private $_numRecords;
	/** @var int number of fields (columns) */
    private $_numFields;
	/** @var string content of dbf file */
    private $_dbf;
	/** @var string content of fpt file	*/
    private $_memo;
    /** @var int length of row */
    private $_rowsize;
    /** @var int length of the header information (offset to 1st record) */
    private $_hdrsize;

	/**
	 * constructor
     * @param string $filedbf database file with extension dbf
     * @throws Exception
	 */
    public function __construct($filedbf)
    {
        $this->_cols = array();
        $this->_numFields = 0;
        $this->_numRecords = 0;
		$this->_dbf = '';
		$this->_memo = '';

        $extension = substr($filedbf, -3);
        if (strtolower($extension) !== 'dbf') {
            throw new Exception('File must have extension dbf');
        }

        $this->_dbf = file_get_contents($filedbf);

        // read header info
        $headerHex = array();
        for ($i=0; $i<32; $i++){
            $headerHex[$i] = str_pad(dechex(ord($this->_dbf[$i]) ), 2, '0', STR_PAD_LEFT);
        }

        // initial information
        $line = 32; //Header Size
        $this->_numRecords = hexdec($headerHex[7].$headerHex[6].$headerHex[5].$headerHex[4]);
        $this->_hdrsize = hexdec($headerHex[9].$headerHex[8]); //Header Size+Field Descriptor
        $this->_rowsize = hexdec($headerHex[11].$headerHex[10]);
		$this->_numFields = floor(($this->_hdrsize - $line) / $line);

        // field properties
        for ($j=0; $j<$this->_numFields; $j++) {
            $name = '';
            $beg = $j*$line+$line;
            for ($k = $beg; $k<$beg+11; $k++){
                if(ord($this->_dbf[$k])!=0){
                    $name .= $this->_dbf[$k];
                }
            }
            $this->_cols[$j]['name'] = $name;
            $this->_cols[$j]['len'] = ord($this->_dbf[$beg+16]);
            $this->_cols[$j]['type'] = $this->_dbf[$beg+11];
        }

        $filememo = substr($filedbf, 0, -3).($extension === 'DBF' ? 'FPT' : 'fpt');

        if (!is_file($filememo)) {
            throw new Exception('File '.$filememo.' not found');
        }

        $this->_memo = file_get_contents($filememo);
    }

	/**
	 * Returns record as an indexed or associative array.
	 * If record is marked as 'deleted' returns false.
	*
	* @param int $recordNumber index of record (from 0)
	* @param bool $assoc true/false = returns associative/indexed array
	* @return array
	*/
    public function getRecord($recordNumber, $assoc = FALSE)
    {
        $ret = array();
        $rawrow = substr($this->_dbf, $recordNumber * $this->_rowsize + $this->_hdrsize, $this->_rowsize);
        $beg = 1;

        if (ord($rawrow[0]) == 42) { // record is deleted
            return FALSE;
        }

        for ($i=0; $i<$this->_numFields; $i++) {
            $col = trim(substr($rawrow, $beg, $this->_cols[$i]['len']));
            if ($this->_cols[$i]['type'] !== 'M') {
                if ($assoc) {
                    $ret[$this->_cols[$i]['name']] = $col;
                } else {
                	$ret[] = $col;
                }
            } else {
                // memo
                $memoStart = ($col*64)+8; // memo field starts with 8 byte (info about memo data)
                // length of memo is stored before memo data
                $byteHex1 = str_pad(dechex(ord($this->_memo[($memoStart-1)]) ), 2, '0', STR_PAD_LEFT);
                $byteHex2 = str_pad(dechex(ord($this->_memo[($memoStart-2)]) ), 2, '0', STR_PAD_LEFT);
                $memoLength = hexdec($byteHex2.$byteHex1);
                if ($assoc) {
                    $ret[$this->_cols[$i]['name']] = substr($this->_memo, $memoStart, $memoLength);
                } else {
                	$ret[] = substr($this->_memo, $memoStart, $memoLength);
                }
            }
            $beg += $this->_cols[$i]['len'];
        }
        return $ret;
    }

    /**
     * returns number of records in dbf file
     * @return int
     */
    public function numRecords()
    {
        return $this->_numRecords;
    }

}
