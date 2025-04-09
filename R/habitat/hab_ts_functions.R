library(dataRetrieval)

#HABITAT FUNCTIONS
clean_historic <- function(historic){
  # ******************************************************************************************
  # Remove any rows with "Ice", "P Ice" or "P Eqp" values for Flow_cd
  # ******************************************************************************************
  if (length(which(historic[,5]== "Ice")) != 0 ){
    print("Removing Ice Flows")
    historic <- historic[-which(historic[,5]== "Ice"),]
  }

  if (length(which(historic[,5]== "P Ice")) != 0 ){
    print("Removing Ice Flows")
    historic <- historic[-which(historic[,5]== "P Ice"),]
  }

  if (length(which(historic[,5]== "P Eqp")) != 0 ){
    print("Removing P Eqp Flows")
    historic <- historic[-which(historic[,5]== "P Eqp"),]
  }

  if (length(which(historic[,4] < 0.0)) != 0 ){
    print("Removing Negative Flow Values")
    historic <- historic[-which(historic[,4] < 0.0),]
  }

  historic <- historic
}
f_fxn <- function(gage,factor){
gage_data <- readNWISdv(gage,'00060') #Retrieve all historic gage streamflow data in cfs
gage_data <- clean_historic(gage_data)

f <- renameNWISColumns(gage_data)
f[,4] <- (f[,4])-((f[,4])*factor)
f <- f
}
flow.ts.range_fxn <- function(f,timespan){
  flow.ts <- f[,3:4] #extract time series as the 3rd and 4th columns
  f4 <- as.numeric(f[,4]) #read 4th column of USGS data (mean daily flow)
  # Indicate desired range for time series

  if (timespan == "all") {
    flow.ts.range <- flow.ts #entire period of record
  } else if (timespan == "current_year"){
    flow.ts.range <- flow.ts[(dim(flow.ts)[1]-365):dim(flow.ts)[1],] #most recent year
  } else {
    flow.ts.range <- flow.ts[(dim(flow.ts)[1]-3650):dim(flow.ts)[1],] #most recent 10 years
  }

}
wua.at.q_fxn <- function(flow.ts.range, wua_table){
  q <- as.numeric(flow.ts.range[,2]) #extract just the flows from flow time series

  targets <- colnames(wua_table)[-1]
  ### Extract all WUA values for a SERIES of flows
  # Initialize results matrix
  wua.at.q <- matrix(0, nrow=length(q), ncol=length(targets))
  rownames(wua.at.q) <- q
  colnames(wua.at.q) <- targets

  for (i in 1:length(q)) {
    currentq <- q[i] #keep track of what flow the loop is on

    # Check if currentq is NA
    if (is.na(currentq) == "TRUE") {
      wua.at.q[i,] <- NA
      next
    }

    # Check if currentq is beyond range of WUA table
    min.q <- min(wua_table[,1])
    max.q <- max(wua_table[,1])
    if (currentq < min.q) {
      wua.at.q[i,] <- NA
      next
    } else if (currentq > max.q) {
      wua.at.q[i,] <- NA
      next
    }

    rowkey <- which.min(abs(as.numeric(wua_table[,1]) - currentq)) #find flow closest to desired q

    # Determine the given flows (and row indices) that bound the desired flow
    if ((as.numeric(wua_table[rowkey,1]) - currentq) > 0) {
      uplim.key <- which.min(abs(as.numeric(wua_table[,1]) - currentq))
      lowlim.key <- uplim.key - 1
    } else if ((as.numeric(wua_table[rowkey,1]) - currentq) < 0) {
      lowlim.key <- which.min(abs(as.numeric(wua_table[,1]) - currentq))
      uplim.key <- lowlim.key + 1
    } else if ((as.numeric(wua_table[rowkey,1]) - currentq) == 0) {
      lowlim.key <- which.min(abs(as.numeric(wua_table[,1]) - currentq))
      uplim.key <- which.min(abs(as.numeric(wua_table[,1]) - currentq))
    }

    # Interpolate (linear) btwn WUA values for bounding flows
    for (j in 1:length(targets)) {
      if (lowlim.key == uplim.key) {
        wua.at.q[i,j] <- as.numeric(wua_table[lowlim.key, j+1])
      } else {
        #linear interpolation: Yq = Ylow + ((Yup-Ylow)*(Xq-Xlow)/(Xup-Xlow))
        Xup <- as.numeric(wua_table[uplim.key, 1])
        Yup <- as.numeric(wua_table[uplim.key, j+1])
        Xlow <- as.numeric(wua_table[lowlim.key, 1])
        Ylow <- as.numeric(wua_table[lowlim.key, j+1])
        wua.at.q[i,j] <- Ylow + ((Yup-Ylow)*(currentq-Xlow)/(Xup-Xlow))
      }
    }
  }
  wua.at.q <- wua.at.q
  return(wua.at.q)
}
hab_ts_plot_fxn <- function(flow.ts.range,wua.at.q,filename,ifim_site_name){


filepath <- paste(save_directory,"\\",filename,sep="")
png(filepath, width = 1400, height = 800,res=150)
### Plot Habitat and Flow Time Series together

layout(matrix(c(1,2), 1, 2), widths=c(7.5,2.5))
#layout.show(2)
par(mar=c(5,5,2,5), xpd=TRUE)
matplot(flow.ts.range[,1], wua.at.q, pch=1, col=rainbow(dim(wua.at.q)[2]),
        xaxt="n", xlab="Time", ylab="Weighted Usable Area (ft2/1000 ft)")
dates <- flow.ts.range[,1]
interval <- seq(1, length(dates), by=90) #every 90 days
date.label <- format(dates[interval], "%m/%d/%y")
axis(side=1, at=dates[interval], labels=date.label, cex.axis=0.9)

par(new=TRUE)
plot(flow.ts.range[,1], as.numeric(rownames(wua.at.q)), col="black", type="l", axes=F,
     xlab=NA, ylab=NA)
axis(side=4)
mtext(side=4, line=3, "Flow (cfs)")
#par(mfrow=c(1,1))

par(new=FALSE)
par(mar=c(0,0,0,0))
plot(flow.ts.range[,1], rownames(wua.at.q), type="n", axes=FALSE,
     xlab=NA, ylab=NA)
legend("left", c(colnames(wua.at.q), "Flow"),
       pch=c(rep(1, dim(wua.at.q)[2]), NA),
       lty=c(rep(NA, dim(wua.at.q)[2]), 1),
       col=c(rainbow(dim(wua.at.q)[2]), "black"))



### Plot flow time series BELOW habitat time series
layout(matrix(c(1,2,3,4), 2, 2), widths=c(8,2), heights=c(2,1)) #set layout
#layout.show(4)

#plot habitat time series (position 1)
par(mar=c(1, 4.1, 2, 1)) #adjust margins for first plot (bottom, left, top, right)
matplot(flow.ts.range[,1], wua.at.q, pch=1, xaxt="n",
        col=rainbow(dim(wua.at.q)[2]),
        xlab=NA, ylab="Weighted Usable Area (ft2/1000 ft)",
        main=paste("Habitat Time Series at ",ifim_site_name," (USGS ", gage,")", sep=""))
dates <- flow.ts.range[,1]
interval <- seq(1, length(dates), by=90) #every 90 days
date.label <- format(dates[interval], "%m/%d/%y")
axis(side=1, at=dates[interval], labels=date.label, cex.axis=0.9)

#plot flow time series (position 2)
par(mar=c(4.1,4.1,1,1)) #adjust margins for second plot
plot(flow.ts.range[,1], as.numeric(rownames(wua.at.q)), col="blue", type="l", xaxt="n",
     xlab="Time", ylab="Flow (cfs)")
axis(side=1, at=dates[interval], labels=date.label, cex.axis=0.9)

#plot habitat time series legend (position 3)
par(mar=c(0,0,0,0))
plot(flow.ts.range[,1], rownames(wua.at.q), type="n", axes=FALSE,
     xlab=NA, ylab=NA)
legend("left", colnames(wua.at.q),
       pch=1,
       col=rainbow(dim(wua.at.q)[2]))

#plot flow time series legend (position 4)
par(mar=c(0,0,0,0))
plot(flow.ts.range[,1], rownames(wua.at.q), type="n", axes=FALSE,
     xlab=NA, ylab=NA)
#legend("left", "Flow", lty=1, col="blue") #remove...too redundant


#filepath <- paste(save_directory,"\\","testplot_",ifim_featureid,".png",sep="")
#png(filepath, width = 800, height = 600)
dev.off()

}


hab_ts_plot_single_fxn <- function(flow.ts.range,wua.at.q,filename,ifim_site_name,ifim_metric){

  wua.at.q <- data.frame(wua.at.q[,ifim_metric])
  colnames(wua.at.q) <- c(ifim_metric)

  layout(matrix(c(1,2), 1, 2), widths=c(7.5,2.5))

  ### Plot flow time series BELOW habitat time series
  layout(matrix(c(1,2,3,4), 2, 2), widths=c(8,2), heights=c(2,1)) #set layout
  #layout.show(4)

  #plot habitat time series (position 1)
  par(mar=c(1, 4.1, 2, 1)) #adjust margins for first plot (bottom, left, top, right)
  matplot(flow.ts.range[,1], wua.at.q, pch=1, xaxt="n",
          col=rainbow(dim(wua.at.q)[2]),
          xlab=NA, ylab="Weighted Usable Area (ft2/1000 ft)",
          main=paste("Habitat Time Series at ",ifim_site_name," (USGS ", gage,")", sep=""))
  dates <- flow.ts.range[,1]
  interval <- seq(1, length(dates), by=90) #every 90 days
  date.label <- format(dates[interval], "%m/%d/%y")
  axis(side=1, at=dates[interval], labels=date.label, cex.axis=0.9)

  #plot flow time series (position 2)
  par(mar=c(4.1,4.1,1,1)) #adjust margins for second plot
  plot(flow.ts.range[,1], flow.ts.range[,2], log="y", col="blue", type="l", xaxt="n",
       xlab="Time", ylab="Flow (cfs)")
  axis(side=1, at=dates[interval], labels=date.label, cex.axis=0.9)

  #plot habitat time series legend (position 3)
  par(mar=c(0,0,0,0))
  plot(flow.ts.range[,1], rownames(wua.at.q), type="n", axes=FALSE,
       xlab=NA, ylab=NA)
  legend("left", colnames(wua.at.q),
         pch=1,
         col=rainbow(dim(wua.at.q)[2]))

  #plot flow time series legend (position 4)
  par(mar=c(0,0,0,0))
  #plot(flow.ts.range[,1], rownames(wua.at.q), type="n", axes=FALSE,
  plot(flow.ts.range[,1], rownames(wua.at.q), type="n", axes=FALSE,
       xlab=NA, ylab=NA)


}
hab_ts_plot_overlay_fxn <- function(flow.ts.range_0,wua.at.q_0,flow.ts.range_10,wua.at.q_10,filename,ifim_site_name,ifim_metric){

  #print(head(flow.ts.range_0))
  #print(head(wua.at.q_0))
  #print(head(flow.ts.range_10))
  #print(head(wua.at.q_10))

  wua.at.q_0 <- data.frame(wua.at.q_0[,ifim_metric])
  colnames(wua.at.q_0) <- c(ifim_metric)

  wua.at.q_10 <- data.frame(wua.at.q_10[,ifim_metric])
  colnames(wua.at.q_10) <- c(ifim_metric)

  layout(matrix(c(1,2), 1, 2), widths=c(7.5,2.5))

  ### Plot flow time series BELOW habitat time series
  layout(matrix(c(1,2,3,4), 2, 2), widths=c(8,2), heights=c(2,1)) #set layout
  #layout.show(4)

  #plot habitat time series (position 1)
  par(mar=c(1, 4.1, 2, 1)) #adjust margins for first plot (bottom, left, top, right)
  matplot(flow.ts.range_0[,1], wua.at.q_0, pch=1, xaxt="n",
          col="blue",
          xlab=NA, ylab="Weighted Usable Area (ft2/1000 ft)",
          main=paste("Habitat Time Series at ",ifim_site_name," (USGS ", gage,")", sep=""))
  dates <- flow.ts.range_0[,1]
  interval <- seq(1, length(dates), by=90) #every 90 days
  date.label <- format(dates[interval], "%m/%d/%y")
  axis(side=1, at=dates[interval], labels=date.label, cex.axis=0.9)
  #-------------------------------------------------------------------
  # OVERLAY HAB TS REDUCED BY 10%
  matplot(flow.ts.range_10[,1], wua.at.q_10, add = TRUE, pch=1, xaxt="n",col="red")
  #-------------------------------------------------------------------

  #plot flow time series (position 2)
  par(mar=c(4.1,4.1,1,1)) #adjust margins for second plot
  plot(flow.ts.range_0[,1], flow.ts.range_0[,2], log="y", col="blue", type="l", xaxt="n",
       xlab="Time", ylab="Flow (cfs)")
  axis(side=1, at=dates[interval], labels=date.label, cex.axis=0.9)
  #-------------------------------------------------------------------
  # OVERLAY FLOW TS REDUCED BY 10%
  points(flow.ts.range_10[,1], flow.ts.range_10[,2], col="red", type="l", xaxt="n")
  #-------------------------------------------------------------------

  #plot habitat time series legend (position 3)
  par(mar=c(0,0,0,0))
  plot(flow.ts.range_0[,1], rownames(wua.at.q_0), type="n", axes=FALSE,
       xlab=NA, ylab=NA)
  legend("left", legend=c(colnames(wua.at.q_0),"flow reduced by 10%"),
         pch=1,
         col=c("blue","red"))


  #plot flow time series legend (position 4)
  par(mar=c(0,0,0,0))
  #plot(flow.ts.range_0[,1], rownames(wua.at.q_0), type="n", axes=FALSE,
  plot(flow.ts.range_0[,1], rownames(wua.at.q_0), type="n", axes=FALSE,
       xlab=NA, ylab=NA)
  legend("left", legend=c("gage flow","gage flow (reduced by 10%)"),
         lty=1,
         col=c("blue","red"))

}
hab_ts_plot_multi_fxn <- function(inputs,filename,ifim_site_name,ifim_metric){


    flow.ts.range_0 <- inputs$flow.ts.range_0
    wua.at.q_0 <- inputs$wua.at.q_0
        wua.at.q_0 <- data.frame(wua.at.q_0[,ifim_metric])
        colnames(wua.at.q_0) <- c(ifim_metric)
    flow.ts.range_5 <- inputs$flow.ts.range_5
    wua.at.q_5 <- inputs$wua.at.q_5
        wua.at.q_5 <- data.frame(wua.at.q_5[,ifim_metric])
        colnames(wua.at.q_5) <- c(ifim_metric)
    flow.ts.range_10 <- inputs$flow.ts.range_10
    wua.at.q_10 <- inputs$wua.at.q_10
        wua.at.q_10 <- data.frame(wua.at.q_10[,ifim_metric])
        colnames(wua.at.q_10) <- c(ifim_metric)
    flow.ts.range_20 <- inputs$flow.ts.range_20
    wua.at.q_20 <- inputs$wua.at.q_20
        wua.at.q_20 <- data.frame(wua.at.q_20[,ifim_metric])
        colnames(wua.at.q_20) <- c(ifim_metric)
    flow.ts.range_30 <- inputs$flow.ts.range_30
    wua.at.q_30 <- inputs$wua.at.q_30
        wua.at.q_30 <- data.frame(wua.at.q_30[,ifim_metric])
        colnames(wua.at.q_30) <- c(ifim_metric)
    flow.ts.range_40 <- inputs$flow.ts.range_40
    wua.at.q_40 <- inputs$wua.at.q_40
        wua.at.q_40 <- data.frame(wua.at.q_40[,ifim_metric])
        colnames(wua.at.q_40) <- c(ifim_metric)
    flow.ts.range_50 <- inputs$flow.ts.range_50
    wua.at.q_50 <- inputs$wua.at.q_50
        wua.at.q_50 <- data.frame(wua.at.q_50[,ifim_metric])
        colnames(wua.at.q_50) <- c(ifim_metric)



  layout(matrix(c(1,2), 1, 2), widths=c(7.5,2.5))

  ### Plot flow time series BELOW habitat time series
  layout(matrix(c(1,2,3,4), 2, 2), widths=c(8,2), heights=c(2,1)) #set layout
  #layout.show(4)

  #plot habitat time series (position 1)
  par(mar=c(1, 4.1, 2, 1)) #adjust margins for first plot (bottom, left, top, right)
  matplot(flow.ts.range_0[,1], wua.at.q_0, pch=1, xaxt="n",
          col="blue",
          xlab=NA, ylab="Weighted Usable Area (ft2/1000 ft)",
          main=paste("Habitat Time Series at ",ifim_site_name," (USGS ", gage,")", sep=""))
  dates <- flow.ts.range_0[,1]
  interval <- seq(1, length(dates), by=90) #every 90 days
  date.label <- format(dates[interval], "%m/%d/%y")
  axis(side=1, at=dates[interval], labels=date.label, cex.axis=0.9)
  #-------------------------------------------------------------------
  # OVERLAY HAB TS REDUCED BY 10% (POINTS)
  matplot(flow.ts.range_5[,1], wua.at.q_5, add = TRUE, pch=1, xaxt="n",col="green")
  matplot(flow.ts.range_10[,1], wua.at.q_10, add = TRUE, pch=1, xaxt="n",col="firebrick")
  matplot(flow.ts.range_20[,1], wua.at.q_20, add = TRUE, pch=1, xaxt="n",col="forestgreen")
  matplot(flow.ts.range_30[,1], wua.at.q_30, add = TRUE, pch=1, xaxt="n",col="orange")
  matplot(flow.ts.range_40[,1], wua.at.q_40, add = TRUE, pch=1, xaxt="n",col="black")
  matplot(flow.ts.range_50[,1], wua.at.q_50, add = TRUE, pch=1, xaxt="n",col="violet")

  # OVERLAY HAB TS REDUCED BY 10% (LINES)
  #matplot(flow.ts.range_5[,1], wua.at.q_5, add = TRUE, type="l", xaxt="n",col="firebrick")
  #matplot(flow.ts.range_10[,1], wua.at.q_10, add = TRUE, type="l", xaxt="n",col="blue")
  #matplot(flow.ts.range_20[,1], wua.at.q_20, add = TRUE, type="l", xaxt="n",col="forestgreen")
  #matplot(flow.ts.range_30[,1], wua.at.q_30, add = TRUE, type="l", xaxt="n",col="orange")
  #matplot(flow.ts.range_40[,1], wua.at.q_40, add = TRUE, type="l", xaxt="n",col="black")
  #matplot(flow.ts.range_50[,1], wua.at.q_50, add = TRUE, type="l", xaxt="n",col="violet")

  #-------------------------------------------------------------------

  #plot flow time series (position 2)
  par(mar=c(4.1,4.1,1,1)) #adjust margins for second plot
  plot(flow.ts.range_0[,1], flow.ts.range_0[,2], log="y", col="blue", type="l", xaxt="n",
       xlab="Time", ylab="Flow (cfs)")
  axis(side=1, at=dates[interval], labels=date.label, cex.axis=0.9)
  #-------------------------------------------------------------------
  # OVERLAY FLOW TS REDUCED BY X%
  points(flow.ts.range_5[,1], flow.ts.range_5[,2], col="green", type="l", xaxt="n")
  points(flow.ts.range_10[,1], flow.ts.range_10[,2], col="firebrick", type="l", xaxt="n")
  points(flow.ts.range_20[,1], flow.ts.range_20[,2], col="forestgreen", type="l", xaxt="n")
  points(flow.ts.range_30[,1], flow.ts.range_30[,2], col="orange", type="l", xaxt="n")
  points(flow.ts.range_40[,1], flow.ts.range_40[,2], col="black", type="l", xaxt="n")
  points(flow.ts.range_50[,1], flow.ts.range_50[,2], col="violet", type="l", xaxt="n")
  #-------------------------------------------------------------------



  #plot habitat time series legend (position 3)
  par(mar=c(0,0,0,0))
  plot(flow.ts.range_0[,1], rownames(wua.at.q_0), type="n", axes=FALSE,
       xlab=NA, ylab=NA)
  legend("left", legend=c(colnames(wua.at.q_0),
                          "flow reduced by 5%",
                          "flow reduced by 10%",
                          "flow reduced by 20%",
                          "flow reduced by 30%",
                          "flow reduced by 40%",
                          "flow reduced by 50%"
                          ),
         pch=1,
         col=c("blue",
               "green",
               "firebrick",
               "forestgreen",
               "orange",
               "black",
               "violet"
               ))

  #plot flow time series legend (position 4)
  par(mar=c(0,0,0,0))
  #plot(flow.ts.range_0[,1], rownames(wua.at.q_0), type="n", axes=FALSE,
  plot(flow.ts.range_0[,1], rownames(wua.at.q_0), type="n", axes=FALSE,
       xlab=NA, ylab=NA)
  legend("left", legend=c("gage flow",
                          "reduced by 5%",
                          "reduced by 10%",
                          "reduced by 20%",
                          "reduced by 30%",
                          "reduced by 40%",
                          "reduced by 50%"
                          ),
         lty=1,
         col=c("blue",
               "green",
               "firebrick",
               "forestgreen",
               "orange",
               "black",
               "violet"
              ))

}

#-------------------
