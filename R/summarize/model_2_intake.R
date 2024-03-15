########################################
###### Facility Model PID to Intake Hydroid Algorithm
########################################

model_2_intake <- function(pid,site,intake.df){
  message("FETCHING INTAKE HYDROID FROM FACILITY:RIVERSEG MODEL PID...")

  #RETRIEVE FACILITY HYDROID FROM FACILITY:RIVERSEG MODEL PID
  invisible(capture.output(model.dataframe <- getProperty(list(pid = as.numeric(pid)), site))) #PRINT MESSAGES SUPPRESSED
  if (isFALSE(model.dataframe) == TRUE) {stop("NO MODEL ASSOCIATED WITH PID SUPPLIED")}
  facility.hydroid <- model.dataframe$featureid
  
  #RETRIEVE MP FEATURES ATTACHED TO FACILITY WITH dh_link_facility_mps
  invisible(capture.output(mps.dataframe <- getFeature(list(dh_link_facility_mps = as.numeric(facility.hydroid)), base_url=site,token=token))) #PRINT MESSAGES SUPPRESSED
  if (isFALSE(mps.dataframe) == TRUE) {stop("NO MPs ASSOCIATED WITH PID SUPPLIED")}
  
  #RESTRICT MPs TO INTAKES ONLY
  intakes <- sqldf("SELECT hydroid, name, fstatus 
                    FROM 'mps.dataframe' 
                    WHERE bundle = 'intake'")

  #DETERMINE RIVERSEG ASSOCIATED WITH FACILITY:RIVERSEG MODEL
  riverseg.inputs <- list(
    varkey = "om_class_textField",
    propname = "riverseg",
    featureid = as.numeric(pid),
    entity_type = "dh_properties"
  )
  invisible(capture.output(riverseg.dataframe <- getProperty(riverseg.inputs, site))) #PRINT MESSAGES SUPPRESSED
  riverseg.code <- riverseg.dataframe$propcode
  # message(paste("RIVERSEG = ",riverseg.code,sep=""))
  
  #INITIALIZE EMPTY INTAKE DATAFRAME
  intake.df <- data.frame(hydroid = character(),
                          name = character(),
                          fstatus = character(),
                          stringsAsFactors = FALSE) 

  #LOOP THOUGH INTAKES TO DETERMINE WHICH IS/ARE LOCATED WITHIN THE RIVERSEG
  for (i in 1:length(intakes[,1])) {
    
    intake.hydroid_i <- intakes$hydroid[i]
    intake.name_i <- intakes$name[i]
    intake.fstatus_i <- intakes$fstatus[i]
    containing_watersheds <- read.csv(paste(site,'dh-feature-contained-within-export', intake.hydroid_i, 'watershed', sep = '/'), header=TRUE, sep=",")
    containing_riverseg <- sqldf(paste("SELECT * 
                                        FROM 'containing_watersheds' 
                                        WHERE ftype = 'vahydro' 
                                        AND hydrocode LIKE '%",riverseg.code,"'",sep=""))
    colnames(containing_riverseg) <- paste("rseg", colnames(containing_riverseg), sep = ".")
    containing_riverseg <- cbind(intake.fstatus = intake.fstatus_i, containing_riverseg)
    containing_riverseg <- cbind(intake.name = intake.name_i, containing_riverseg)
    containing_riverseg <- cbind(intake.hydroid = intake.hydroid_i, containing_riverseg)

    if (length(containing_riverseg[,1]) == 0) {
      stop(paste("NO INTAKES IN RIVERSEG: ",riverseg.code,sep=""))
    }
    
    intake.df  <- rbind(intake.df, containing_riverseg)
  } #CLOSE FOR LOOP

  #PRINT THE HYDROID OF INTAKE(S) WITHIN RIVERSEG
  intake.hydroid <- intake.df$intake.hydroid
  if (length(intake.df[, 1]) == 1) {
    message(paste("INTAKE HYDROID = ", intake.hydroid, sep = ""))
  } else if (length(intake.df[, 1]) > 1) {
    message("FACILITY HAS MUTIPLE INTAKES WITHIN RIVERSEG")
    message(intake.hydroid)
  }
 
  #RETURN DATAFRAME OF RIVERSEG & INTAKE(S)
  intake.df <- intake.df
  return(intake.df)
 
} #CLOSE FUNCTION 
