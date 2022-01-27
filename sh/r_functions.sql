-- R Functions
CREATE EXTENSION plr;

-- Aggregates
DROP AGGREGATE array_accum(anycompatible) ;
CREATE AGGREGATE array_accum(
  BASETYPE=anycompatible ,
  SFUNC=array_append,
  STYPE=anycompatiblearray,
  INITCOND='{}'
);


-- Functions
-- QUANTILE
-- Usage: select r_quantile(array_accum(y), 0.6) from lmtab;
CREATE OR REPLACE FUNCTION r_quantile(double precision[], double precision) RETURNS double precision AS '
quantile(arg1,arg2)
' LANGUAGE 'plr' STRICT;


-- LM (linear Model)
-- Usage: select r_lm(array[array_accum(y), array_accum(x1)]) from lmtab;
-- Note: Currently only works for a single x value, soon to work for multiple x
CREATE OR REPLACE FUNCTION r_lm(double precision[])
  RETURNS double precision AS '
Y<-arg1[1,1:dim(arg1)[2]]
X<-data.matrix(arg1[2:dim(arg1)[1],1:dim(arg1)[2]])
X1<-as.data.frame(X)
res.lm<- lm(Y ~ X, na.action = na.exclude)
cor(Y,predict(res.lm,X1))
'  LANGUAGE 'plr' STRICT;

-- LM (linear Model), returns array containing rsquare, coefficients and pvalues
-- Usage: select r_lm(array[array_accum(y), array_accum(x1)]) from lmtab;
-- Note: Currently only works for a single x value, soon to work for multiple x
-- result: {R2,c-b,c-m,p-b,p-m]
CREATE OR REPLACE FUNCTION r_lm_cp(double precision[])
  RETURNS double precision[] AS '
lmret = array()
Y<-arg1[1,1:dim(arg1)[2]]
X<-data.matrix(arg1[2:dim(arg1)[1],1:dim(arg1)[2]])
X1<-as.data.frame(X)
res.lm<- lm(Y ~ X, na.action = na.exclude)
lmret[1] = cor(Y,predict(res.lm,X1))
lm.res = summary(res.lm)
cf = coef(res.lm)
lmret[2] = cf[[1]] 
lmret[3] = cf[[2]] 
lmret[4] = lm.res[[4]][[7]] 
lmret[5] = lm.res[[4]][[8]] 
return(lmret)
'  LANGUAGE 'plr' STRICT;

-- MEDIAN
-- Usage: select median(y) from lmtab;
-- Note: Currently only works for a single x value, soon to work for multiple x
CREATE OR REPLACE FUNCTION r_median(double precision[]) RETURNS double precision  AS '
  median(arg1)
' LANGUAGE 'plr' STRICT;


drop AGGREGATE median ( double precision);
CREATE AGGREGATE median (
    BASETYPE = double precision,
    SFUNC = plr_array_accum,
    STYPE = double precision[],
    FINALFUNC = r_median
);


-- KMEANS 
-- Usage: select kmeans(array_accum(y),2,2007) from lmtab;
-- Note: Currently only works for a single x value, soon to work for multiple x
CREATE OR REPLACE FUNCTION kmeans(double precision[], int4, int4)
  RETURNS double precision[] AS'
set.seed(arg3)
km=kmeans(sort(arg1),arg2)
sort(unlist(tapply(sort(arg1),factor(match(km$cluster,order(km$centers))),range)))
' LANGUAGE 'plr' VOLATILE STRICT;


-- Gini 
-- Usage: select gini(array_accum(x)) from gnums;
-- Note: Performs a calculation of the Gini coefficient for the given data set

CREATE OR REPLACE FUNCTION gini(double precision[])
  RETURNS double precision AS '
  library(reldist)
  gini(arg1,c(1:length(arg1)))
'  LANGUAGE 'plr' VOLATILE STRICT;
