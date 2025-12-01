program LegacyCSV;

{$mode objfpc}{$H+}

uses
  SysUtils, DateUtils, Unix;

function GetEnvDef(const name, def: string): string;
var v: string;
begin
  v := GetEnvironmentVariable(name);
  if v = '' then Exit(def) else Exit(v);
end;

function RandFloat(minV, maxV: Double): Double;
begin
  Result := minV + Random * (maxV - minV);
end;

function RandBool(): Boolean;
begin
  Result := Random(2) = 1;
end;

function FormatTimestamp(ts: TDateTime): string;
begin
  Result := FormatDateTime('yyyy-mm-dd"T"hh:nn:ss"Z"', ts);
end;

function FormatBoolean(b: Boolean): string;
begin
  if b then Result := 'ИСТИНА' else Result := 'ЛОЖЬ';
end;

function FormatNumber(n: Double): string;
begin
  Result := FormatFloat('0.00', n);
end;

procedure GenerateAndCopy();
var
  outDir, fn, fullpath, pghost, pgport, pguser, pgpass, pgdb, copyCmd: string;
  f: TextFile;
  ts: TDateTime;
  voltage, temp: Double;
  isActive: Boolean;
  recordId: Integer;
begin
  outDir := GetEnvDef('CSV_OUT_DIR', '/data/csv');
  ts := Now;
  fn := 'telemetry_' + FormatDateTime('yyyymmdd_hhnnss', ts) + '.csv';
  fullpath := IncludeTrailingPathDelimiter(outDir) + fn;

  // write CSV with proper formatting
  AssignFile(f, fullpath);
  Rewrite(f);
  
  // Header
  Writeln(f, 'recorded_at,voltage,temp,is_active,record_id,source_file');
  
  // Generate multiple records for demonstration
  for recordId := 1 to 10 do
  begin
    ts := IncSecond(Now, -recordId * 60); // Different timestamps
    voltage := RandFloat(3.2, 12.6);
    temp := RandFloat(-50.0, 80.0);
    isActive := RandBool();
    
    // Write with proper formatting:
    // - Timestamp: ISO 8601 format
    // - Numbers: numeric format (no quotes)
    // - Boolean: ИСТИНА/ЛОЖЬ
    // - Strings: text format
    Writeln(f, 
      FormatTimestamp(ts) + ',' +
      FormatNumber(voltage) + ',' +
      FormatNumber(temp) + ',' +
      FormatBoolean(isActive) + ',' +
      IntToStr(recordId) + ',' +
      '"' + fn + '"'
    );
  end;
  
  CloseFile(f);

  // COPY into Postgres
  pghost := GetEnvDef('PGHOST', 'db');
  pgport := GetEnvDef('PGPORT', '5432');
  pguser := GetEnvDef('PGUSER', 'monouser');
  pgpass := GetEnvDef('PGPASSWORD', 'monopass');
  pgdb   := GetEnvDef('PGDATABASE', 'monolith');

  // Используем ExecProcess для выполнения psql
  copyCmd := 'PGPASSWORD=' + pgpass + ' psql "host=' + pghost + ' port=' + pgport + ' user=' + pguser + ' dbname=' + pgdb + '" ' +
             '-c "\copy telemetry_legacy(recorded_at, voltage, temp, is_active, record_id, source_file) FROM ''' + fullpath + ''' WITH (FORMAT csv, HEADER true)"';
  
  fpSystem(copyCmd);
end;

var period: Integer;
begin
  Randomize;
  period := StrToIntDef(GetEnvDef('GEN_PERIOD_SEC', '300'), 300);
  while True do
  begin
    try
      GenerateAndCopy();
    except
      on E: Exception do
        WriteLn('Legacy error: ', E.Message);
    end;
    Sleep(period * 1000);
  end;
end.
