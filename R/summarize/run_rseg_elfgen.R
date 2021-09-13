#----------------------------------------------
site <- "http://deq2.bse.vt.edu/d.dh"    #Specify the site of interest, either d.bet OR d.dh
#----------------------------------------------
# Load Libraries
basepath='/var/www/R';

source(paste(basepath,'config.R', sep='/'))
source(paste(om_location,'R/summarize','rseg_elfgen.R',sep='/'))
library(stringr)
library(sqldf)
library(elfgen)
library(ggplot2)

# dirs/URLs
save_directory <- "/var/www/html/data/proj3/out"

#------------------------------------------------
#Inputs

# Sample inputs

# riv_seg <- 'PS3_5990_6161' #PS3_5990_6161' #'TU4_8680_8810' 'TU3_9040_9180' random examples for practice
# runid<-11
# pid <- get.overall.vahydro.prop(riv_seg, site = site, token = token)
# huc_level<- 'huc8'
# dataset <- 'VAHydro-EDAS' #'VAHydro-EDAS' or 'IchthyMaps'

# Read Args
argst <- commandArgs(trailingOnly=T)
pid <- as.integer(argst[1])
runid <- as.integer(argst[2])
huc_level <- as.character(argst[3])
dataset <- as.character(argst[4])

# #MANUAL TEST
# pid <- as.integer(4713658)
# runid <- as.integer(11)
# huc_level <- as.character("huc8")
# dataset <- as.character("VAHydro-EDAS")
#
inputs<-list(pid=pid)
property<-getProperty(inputs, site)
hydroid<-property$featureid

elfgen_location <- paste0('/opt/model/elfgen')

elfgen_huc(runid, hydroid, huc_level, dataset)
