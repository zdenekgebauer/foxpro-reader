# FoxProReader

FoxProReader read data from *.dbf and *.fpt from DOS version of FoxPro. Doesn't work for Visual FoxPro files.

## Usage

```php
require '../src/foxpro-reader.php';

try {
    $dbf = new FoxProReader('./TEST26.DBF');
    $recordsTotal = $dbf->numRecords();
    for ($i=0; $i<=$recordsTotal-1; $i++) {
        $row = $dbf->getRecord($i, TRUE);
        var_dump($row);
    }
    unset($dbf);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

**NOTE:** Class read both files (dbf and fpt) into memory, so this could take a lot of memory for large files.
