DROP TABLE SelfDrivingTest CASCADE CONSTRAINTS;
DROP TABLE TestDriver CASCADE CONSTRAINTS;
DROP TABLE Developer CASCADE CONSTRAINTS;
DROP TABLE CarWithDevice CASCADE CONSTRAINTS;
DROP TABLE SelfDrivingSoftwareRecord CASCADE CONSTRAINTS;
DROP TABLE SelfDrivingSoftware CASCADE CONSTRAINTS;
DROP TABLE PathCondition CASCADE CONSTRAINTS;
DROP TABLE Geometry CASCADE CONSTRAINTS;
DROP TABLE Path CASCADE CONSTRAINTS;
DROP VIEW TestView;


CREATE TABLE Developer
		(accountNo INT PRIMARY KEY,
		password INT NOT NULL,
		name CHAR(30) NOT NULL);

CREATE TABLE TestDriver
		(accountNo INT NOT NULL,
    driverID CHAR(30) NOT NULL,
    phoneNo INT NULL,
    PRIMARY KEY (accountNo, driverID),
    FOREIGN KEY (accountNo) REFERENCES Developer
    ON DELETE CASCADE);

CREATE TABLE SelfDrivingSoftware
		(versionID INT PRIMARY KEY,
    updatetime CHAR(30) NULL,
    comment_content CHAR(1000) NULL);

CREATE TABLE SelfDrivingSoftwareRecord
		(swrecordID INT PRIMARY KEY,
    consolelog CHAR(1000) NULL,
    versionID INT NULL,
    FOREIGN KEY (versionID) REFERENCES SelfDrivingSoftware
    ON DELETE SET NULL);

CREATE TABLE CarWithDevice
		(carID INT PRIMARY KEY,
    cartype CHAR(30) NULL,
    deviceID INT NULL,
		versionID INT NULL,
		FOREIGN KEY (versionID) REFERENCES SelfDrivingSoftware
		ON DELETE SET NULL);

CREATE TABLE PathCondition
		(pathcondID INT PRIMARY KEY,
    roadtype CHAR(30) NULL,
    weather CHAR(30) NULL,
    climate CHAR(30) NULL,
    dayornight CHAR(30) NULL);

CREATE TABLE Path
		(pathID INT PRIMARY KEY,
    city CHAR(30) NULL,
    location CHAR(30) NULL,
    startpoint CHAR(30) NULL,
    endpoint CHAR(30) NULL,
    pathcondID INT NULL,
    FOREIGN KEY (pathcondID) REFERENCES PathCondition
    ON DELETE SET NULL);

CREATE TABLE Geometry
		(geoID INT NOT NULL,
    lat INT NULL,
    lon INT NULL,
    pathID INT NOT NULL,
    PRIMARY KEY (geoID, pathID),
    FOREIGN KEY (pathID) REFERENCES Path
    ON DELETE CASCADE);

CREATE TABLE SelfDrivingTest
		(recordID INT PRIMARY KEY,
    status CHAR(30) NULL,
    carID INT NULL,
    swrecordID INT NULL,
    pathID INT NULL,
    accountNo INT NULL,
    driverID CHAR(30) NULL,
    fromdatetime CHAR(30) NULL,
    todatetime CHAR(30) NULL,
    FOREIGN KEY (carID) REFERENCES CarWithDevice,
    FOREIGN KEY (swrecordID) REFERENCES SelfDrivingSoftwareRecord,
    FOREIGN KEY (accountNo, driverID) REFERENCES TestDriver,
    FOREIGN KEY (pathID) REFERENCES Path);

		CREATE VIEW TestView AS
			SELECT SelfDrivingTest.recordID, TestDriver.*, CarWithDevice.*,
					updatetime, comment_content, consolelog, Path.*,
					roadtype, weather, climate, dayornight
			FROM SelfDrivingTest LEFT OUTER JOIN SelfDrivingSoftwareRecord ON SelfDrivingTest.swrecordID = SelfDrivingSoftwareRecord.swrecordID
			, CarWithDevice, SelfDrivingSoftware, TestDriver, Path, PathCondition
			WHERE SelfDrivingTest.carID = CarWithDevice.carID
			AND CarWithDevice.versionID = SelfDrivingSoftware.versionID
			AND SelfDrivingTest.accountNo = TestDriver.accountNo
			AND SelfDrivingTest.pathID = Path.pathID
			AND Path.pathcondID = PathCondition.pathcondID;
