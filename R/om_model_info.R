# TODO: turn this into a file of functions fo inspecting model runs

elid = 353183
runid=601
location = 'remote'
if (location == 'local') {
  path = "/media/model/omdata/proj3/out/"
} else {
  path = paste0(omsite, "/data/proj3/out")
}

report_file = fn$paste0(path, "/", "report$elid","-$runid", ".log")
logfile = fn$paste0(path, "/", "runlog$runid", ".$elid", ".log")
manifest_file = fn$paste0(path,"/", "manifest", ".", runid, ".", elid, ".log")

rtext <- paste(readLines(report_file), collapse="\n")
ltext <- paste(readLines(logfile), collapse="\n")
try (msgtext <- paste(readLines(manifest_file), collapse="\n"))

