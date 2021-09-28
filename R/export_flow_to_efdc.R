library("hydrotools")
elid = 212091;
# runid - vahydro model run id, Ex:
runid = 400
scenario = 'deq80'
# use_cached = TRUE
model_dat <- om_get_rundata(elid, runid, site = omsite)
outfile = paste(
  '/WorkSpace/modeling/tmp/', 'qser_vahydro_',elid,'_',scenario, '.txt'
  ,sep='')
tsbegin = 2557;# First time step to use at date zero
begin_date = '1/1/1997'

# timeseries number (not used by model, info only)
# last ts in original is 49, so:
#   deq40 = 50, deq20 = 51, deq10 = 52
# This IS used in the files for efdc
tsnums = list(
  'deq40' = 50,
  'deq20' = 51,
  'deq10' = 52,
  'deqbase' = 53,
  'deq60' = 54,
  'deq80' = 56,
  'deqscenb' = 55
)
#
# Format = tx, Q, thisdate
# assemble Q and thisdate, then set tx
date_range = "19970101/20091231"
model_formatted <- cbind(
  tx = as.character(index(index(as.xts(model_dat)[date_range]) ) + tsbegin - 1),
  q = as.character(round(as.xts(model_dat)[date_range]$Qout/1.547,1)),
  ts = as.character(format(index(as.xts(model_dat)[date_range]), format = "%m/%d/%Y"))
)
# Count the number of lines of data
mqser = nrow(model_dat)
tcqser = 86400
rmuladj = 0.04381264

h1template = "   1      %i  %i  0.0   %8f  0.0  0.0  !=   %i   %s (MGD->cms)"
layer_mult = "0.12500   0.12500   0.12500   0.12500   0.12500   0.12500   0.12500   0.12500"
header_lines <-
  rbind(
    sprintf(
      h1template,
      mqser, tcqser, rmuladj, as.integer(tsnums[scenario]), scenario
    ),
    layer_mult
  )

# Write header
write.table(
  header_lines,
  outfile,
  col.names = FALSE,
  row.names = FALSE,
  quote = FALSE
)

# Write data
write.table(
  model_formatted,
  outfile,
  col.names = FALSE,
  row.names = FALSE,
  quote = FALSE,
  append = TRUE,
  sep="\t"
)

# Notes:

# Card C7 -- run duration (for making short run tests)
# efdc.inp card C7 firs tcolumn NTC is # of days in simulation, must have
#       sufficient data to fill this.  Looks like 10 days after 2009/12/31
# C7  NTC  NTSPTC NLTC NTTC NTCPP NTSTBC NTCNB NTCVB NTSMMT NFLTMT NDRYSTP      |
# 4758  5760   0    0    800   12      0     0     240   1       2

# efdc.inp Card C23
# NQSER needs to match number of series in qser.inp
#C23 NVBS NUBW NUBE NVBN NQSIJ NQJPIJ NQSER NQCTL NQCTLT NQWR NQWRSR  ISDIQ    |
#  0    0    0    0    49    0      49       0     0       0     0       0  |
# Changes to this when we add 4 DEQ specific timeseries into qser.inp
#  0    0    0    0    49    0      53       0     0       0     0       0
# Changes to this when we add 5 DEQ specific timeseries into qser.inp
#  0    0    0    0    49    0      54       0     0       0     0       0

# Card C24
# C24 VOLUMETRIC SOURCE/SINK LOCATIONS, MAGNITUDES, AND CONCENTRATION SERIES    |
# C24 IQS  JQS   QSSE  NQSMUL NQSMFF NQSERQ NS-  NT- ND- NSF- NTX- NSD- NSN-     |
#276	79	0	0	0	54	0	0	15	0	0	0	0	0!	Disc_15   ! C. Mill 276	70

# Qser.inp
# Format: first 2 lines are fixed width (spaces)
#         subsequent timeseries lines are tab delimited
# ISTYPE = 1, 2nd line has one entry for each model layer to distribute inflow
#     Example below shows 8 layers, evenly distruibuted 12.5% to each layer
# MQSER - # of lines of data
# TCQSER - interval length in seconds
# TAQSER - ?? (default 0 used in chick model)
# RMULADJ - conversion to m3/s (cms)
#  ISTYP   MQSER TCQSER TAQSER RMULADJ  ADDADJ
#   1      7627  86400  0.0   0.04381264  0.0  0.0  !=   45   ExpA (MGD->cms)
#0.12500   0.12500   0.12500   0.12500   0.12500   0.12500   0.12500   0.12500
#2557	0.3	1/1/1997
#2558	0.3	1/2/1997
# For inclusion in a model, efdc.inp, table C23,
#   NQSER must equal # of separate timeseries in qser.inp file
#   qser.inp has comments at end of first line begin wtih != to indicate
#   the ID of the timeseries, but these are not used, model just takes
#   them as it comes
