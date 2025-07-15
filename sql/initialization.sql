-- drop all the tables to ensure this script can be ran multiple times
DROP TABLE TrackMeasurement;
DROP TABLE DoesWorkout;
DROP TABLE HasMeal;
DROP TABLE HasNutrition;
DROP TABLE WorkoutIncludeExercise;
DROP TABLE MealContainFood;
DROP TABLE GroupClass;
DROP TABLE Class_Trainer;
DROP TABLE Class_Price;
DROP TABLE Meal;
DROP TABLE Workout;
DROP TABLE Nutrition;
DROP TABLE Exercises;
DROP TABLE Trainer;
DROP TABLE Food;
DROP TABLE UserInfo;

-- User(UserID, userName UNIQUE NOT NULL, Gender, Age)
CREATE TABLE UserInfo(
    UserID VARCHAR(10) PRIMARY KEY,
    Username VARCHAR(10) UNIQUE NOT NULL,
    Gender VARCHAR(8),
    Age INTEGER
);

-- Trainer(TID, TrainerName NOT NULL)
CREATE TABLE Trainer(
    TID VARCHAR(10) PRIMARY KEY,
    TrainerName VARCHAR(10) NOT NULL
);

-- Food(FoodName, FoodCalories)
CREATE TABLE Food(
    FoodName VARCHAR(50) PRIMARY KEY,
    FoodCalories INTEGER DEFAULT 0 NOT NULL
);

-- Exercises(ExerciseName, Category,CaloriesBurned, Intensity)
CREATE TABLE Exercises(
    ExerciseName VARCHAR(25) PRIMARY KEY,
    Category VARCHAR(15),
    CaloriesBurned INTEGER DEFAULT 0 NOT NULL,
    Intensity VARCHAR(10)
);

-- Nutrition(NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
CREATE TABLE Nutrition(
    NID VARCHAR(10) PRIMARY KEY,
    NDate DATE,
    DailyConsumedCalories INTEGER DEFAULT 0 NOT NULL,
    DailyCaloriesGoal INTEGER DEFAULT 0 NOT NULL
);

-- Workout(WID, NID NOT NULL, TotalCaloriesBurned DEFAULT 0 NOT NULL, WorkoutDate NOT NULL, Total Duration NOT NULL)
CREATE TABLE Workout(
    WID VARCHAR(20) PRIMARY KEY,
	NID VARCHAR(10) NOT NULL,
    TotalCaloriesBurned INTEGER DEFAULT 0 NOT NULL,
    WorkoutDate DATE NOT NULL,
    TotalDuration INTEGER NOT NULL,
    FOREIGN KEY (NID) REFERENCES
        Nutrition(NID)
        ON DELETE CASCADE
);

-- Meal(MID, NID, Type, MealCaloriesConsumed)
CREATE TABLE Meal(
    MID VARCHAR(10)  PRIMARY KEY,
	NID VARCHAR(10) NOT NULL,
    Type VARCHAR(10),
    MealCaloriesConsumed INTEGER DEFAULT 0 NOT NULL,
    FOREIGN KEY (NID) REFERENCES
        Nutrition(NID)
        ON DELETE CASCADE
);

-- Class_Trainer(ClassTitle,TID UNIQUE NOT NULL)
CREATE TABLE Class_Trainer(
    ClassTitle VARCHAR(20) PRIMARY KEY,
    TID VARCHAR(10) UNIQUE NOT NULL,
    FOREIGN KEY (TID) REFERENCES
        Trainer(TID)
        ON DELETE CASCADE
);

-- Class_Price(ClassTitle,ClassPrice)
CREATE TABLE Class_Price(
    ClassTitle VARCHAR(20) PRIMARY KEY,
    ClassPrice INTEGER
);

-- GroupClass(WID,ClassTitle, Intensity)
CREATE TABLE GroupClass (
    WID VARCHAR(10) PRIMARY KEY,
    ClassTitle VARCHAR(20),
    Intensity VARCHAR(10),
    FOREIGN KEY (WID) REFERENCES
        Workout(WID)
        ON DELETE CASCADE,
    FOREIGN KEY (ClassTitle) REFERENCES
        Class_Price(ClassTitle)
        ON DELETE CASCADE,
    FOREIGN KEY (ClassTitle) REFERENCES
        Class_Trainer(ClassTitle)
        ON DELETE CASCADE
);

-- WorkoutIncludeExercise(WID, ExerciseName, Duration NOT NULL)
CREATE TABLE  WorkoutIncludeExercise(
    WID VARCHAR(10),
    ExerciseName VARCHAR(10),
    Duration INTEGER,
    PRIMARY KEY(WID, ExerciseName),
    FOREIGN KEY (WID) REFERENCES
        Workout(WID)
        ON DELETE CASCADE,
    FOREIGN KEY (ExerciseName) REFERENCES
    Exercises(ExerciseName)
    ON DELETE CASCADE
);

-- MealContainFood(MID, FoodName)
CREATE TABLE  MealContainFood(
    MID VARCHAR(10),
    FoodName VARCHAR(50),
    Quantity INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY(MID,FoodName),
    FOREIGN KEY(MID) REFERENCES
        Meal(MID)
        ON DELETE CASCADE,
    FOREIGN KEY (FoodName) REFERENCES
        Food(FoodName)
        ON DELETE CASCADE
);

-- DoesWorkout(UID,WID)
CREATE TABLE DoesWorkout(
    UserID VARCHAR(10),
    WID VARCHAR(10),
    PRIMARY KEY(UserID, WID),
    FOREIGN KEY (UserID) REFERENCES
        UserInfo(UserID)
        ON DELETE CASCADE,
    FOREIGN KEY (WID) REFERENCES
        Workout(WID)
        ON DELETE CASCADE
);

-- HasNutrition(UID,NID)
CREATE TABLE HasNutrition(
    UserID VARCHAR(10),
    NID VARCHAR(10),
    PRIMARY KEY(UserID, NID),
    FOREIGN KEY (UserID) REFERENCES
        UserInfo(UserID)
        ON DELETE CASCADE,
    FOREIGN KEY (NID) REFERENCES
        Nutrition(NID)
        ON DELETE CASCADE
);

-- HasMeal(UID,MID)
CREATE TABLE HasMeal(
    UserID VARCHAR(10),
    MID VARCHAR(10),
    PRIMARY KEY(UserID, MID),
    FOREIGN KEY (UserID) REFERENCES
        UserInfo(UserID)
        ON DELETE CASCADE,
    FOREIGN KEY (MID) REFERENCES
        Meal(MID)
        ON DELETE CASCADE
);

-- TrackMeasurement(UID, MDate, Weight, Height, BMI, BMR)
CREATE TABLE TrackMeasurement(
    UserID VARCHAR(10),
    MDate DATE,
    Weight FLOAT,
    Height FLOAT,
    PRIMARY KEY(UserID, MDate),
    FOREIGN KEY (UserID) REFERENCES
        UserInfo(UserID)
        ON DELETE CASCADE
);

INSERT ALL
    INTO UserInfo (UserID, Username, Gender, Age) VALUES ('001', 'userA', 'Female', 42)
    INTO UserInfo (UserID, Username, Gender, Age) VALUES ('002', 'userB', 'Male', 41)
    INTO UserInfo (UserID, Username, Gender, Age) VALUES ('003', 'userC', 'Female', 18)
    INTO UserInfo (UserID, Username, Gender, Age) VALUES ('004', 'userD', 'Male', 19)
    INTO UserInfo (UserID, Username, Gender, Age) VALUES ('005', 'userE', 'Female', 30)
    INTO UserInfo (UserID, Username, Gender, Age) VALUES ('006', 'admin', 'Male', 99)
SELECT * FROM dual;

INSERT ALL
    INTO Trainer(TID, TrainerName) VALUES ('1', 'trainerA')
    INTO Trainer(TID, TrainerName) VALUES ('2', 'trainerB')
    INTO Trainer(TID, TrainerName) VALUES ('3', 'trainerC')
    INTO Trainer(TID, TrainerName) VALUES ('4', 'trainerD')
    INTO Trainer(TID, TrainerName) VALUES ('5', 'trainerE')
    INTO Trainer(TID, TrainerName) VALUES ('6', 'trainerF')
    INTO Trainer(TID, TrainerName) VALUES ('7', 'trainerG')
    INTO Trainer(TID, TrainerName) VALUES ('8', 'trainerH')
    INTO Trainer(TID, TrainerName) VALUES ('9', 'trainerI')
    INTO Trainer(TID, TrainerName) VALUES ('10', 'trainerJ')
SELECT * FROM dual;

INSERT ALL
    INTO Food(FoodName, FoodCalories) VALUES ('Medium Green Apple', 90)
    INTO Food(FoodName, FoodCalories) VALUES ('Banana', 90)
    INTO Food(FoodName, FoodCalories) VALUES ('Chicken Fried Thigh', 200)
    INTO Food(FoodName, FoodCalories) VALUES ('Pizza', 200)
    INTO Food(FoodName, FoodCalories) VALUES ('Black Coffee', 5)
SELECT * FROM dual;

INSERT ALL
    INTO Exercises(ExerciseName, Category, CaloriesBurned, Intensity)
    VALUES	('Jogging', 'Aerobics', 200, 'Low')
    INTO Exercises(ExerciseName, Category, CaloriesBurned, Intensity)
    VALUES	('SpinBiking', 'Cardio', 250, 'Medium')
    INTO Exercises(ExerciseName, Category, CaloriesBurned, Intensity)
    VALUES	('Tango', 'Aerobics', 350, 'High')
    INTO Exercises(ExerciseName, Category, CaloriesBurned, Intensity)
    VALUES	('Volleyball', 'Aerobics', 250, 'Low')
    INTO Exercises(ExerciseName, Category, CaloriesBurned, Intensity)
    VALUES	('Swimming', 'Aerobics', 300, 'Medium')
    INTO Exercises(ExerciseName, Category, CaloriesBurned, Intensity)
    VALUES ('Burpees', 'Cardio', 150, 'High')
SELECT * FROM dual;

INSERT ALL
    INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES ('1', DATE '2023-07-01', 1800, 2000)
    INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES ('2', DATE '2023-09-15', 1556, 1800)
    INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES ('3', DATE '2023-09-20', 1900, 1600)
    INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES ('4', DATE '2023-10-02', 2500, 2300)
    INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES ('5', DATE '2023-10-10', 2600, 2800)
    INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES ('6', DATE '2024-01-05', 2600, 2800)
    INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES ('7', DATE '2024-03-10', 1800, 1900)
    INTO Nutrition (NID, NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES ('8', DATE '2023-03-10', 1800, 1900)
SELECT * FROM dual;

INSERT ALL
    INTO Workout (NID, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	('1', '001', 200, DATE '2023-07-01', 30)
    INTO Workout (NID, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	('2', '002', 650, DATE '2023-09-15', 120)
    INTO Workout (NID, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	('3', '003', 445, DATE '2023-09-20', 36)
    INTO Workout (NID, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	('4', '004', 500, DATE '2023-10-02', 65)
    INTO Workout (NID, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	('5', '005', 110, DATE '2023-10-10', 15)
    INTO Workout (NID, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	('6', '006', 200, DATE '2024-01-05', 15)
    INTO Workout (NID, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	('7', '007', 400, DATE '2024-03-10', 40)
    INTO Workout (NID, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	('8', '008', 400, DATE '2023-03-10', 40)
SELECT * FROM dual;

INSERT ALL
    INTO Meal (NID, MID, Type, MealCaloriesConsumed) VALUES ('1', '1', 'Breakfast', 700)
    INTO Meal (NID, MID, Type, MealCaloriesConsumed) VALUES ('2', '2', 'Snack', 200)
    INTO Meal (NID, MID, Type, MealCaloriesConsumed) VALUES ('3', '3', 'Lunch', 900)
    INTO Meal (NID, MID, Type, MealCaloriesConsumed) VALUES ('4', '4', 'Snack', 200)
    INTO Meal (NID, MID, Type, MealCaloriesConsumed) VALUES ('5', '5', 'Dinner', 600)
SELECT * FROM dual;

INSERT ALL
    INTO Class_Trainer(ClassTitle, TID) VALUES ('Cardio Class', '1')
    INTO Class_Trainer(ClassTitle, TID) VALUES ('Spin Class', '2')
    INTO Class_Trainer(ClassTitle, TID) VALUES ('DanceFit Class', '3')
    INTO Class_Trainer(ClassTitle, TID) VALUES ('Sports Class', '4')
    INTO Class_Trainer(ClassTitle, TID) VALUES ('AquaFit Class', '5')
    INTO Class_Trainer(ClassTitle, TID) VALUES ('HIIT Class', '6')
    INTO Class_Trainer(ClassTitle, TID) VALUES ('Zumba Class', '7')
SELECT * FROM dual;

INSERT ALL
    INTO Class_Price(ClassTitle, ClassPrice) VALUES ('Cardio Class', 100)
    INTO Class_Price(ClassTitle, ClassPrice) VALUES ('Spin Class', 80)
    INTO Class_Price(ClassTitle, ClassPrice) VALUES ('DanceFit Class', 90)
    INTO Class_Price(ClassTitle, ClassPrice) VALUES ('Sports Class', 110)
    INTO Class_Price(ClassTitle, ClassPrice) VALUES ('AquaFit Class', 120)
    INTO Class_Price(ClassTitle, ClassPrice) VALUES ('HIIT Class', 95)
    INTO Class_Price(ClassTitle, ClassPrice) VALUES ('Zumba Class', 150)
SELECT * FROM dual;

INSERT ALL
    INTO GroupClass(WID, ClassTitle, Intensity) VALUES ('001', 'Cardio Class', 'Low')
    INTO GroupClass(WID, ClassTitle, Intensity) VALUES ('002', 'Spin Class', 'Median')
    INTO GroupClass(WID, ClassTitle, Intensity) VALUES ('003', 'DanceFit Class', 'Low')
    INTO GroupClass(WID, ClassTitle, Intensity) VALUES ('004', 'Sports Class', 'Median')
    INTO GroupClass(WID, ClassTitle, Intensity) VALUES ('005', 'AquaFit Class', 'High')
    INTO GroupClass(WID, ClassTitle, Intensity) VALUES ('006', 'HIIT Class', 'High')
    INTO GroupClass(WID, ClassTitle, Intensity) VALUES ('007', 'Zumba Class', 'Median')
    INTO GroupClass(WID, ClassTitle, Intensity) VALUES ('008', 'Cardio Class', 'Low')
SELECT * FROM dual;

INSERT ALL
    INTO WorkoutIncludeExercise (WID, ExerciseName, Duration) VALUES ('001', 'Jogging', 30)
    INTO WorkoutIncludeExercise (WID, ExerciseName, Duration) VALUES ('002', 'SpinBiking', 45)
    INTO WorkoutIncludeExercise (WID, ExerciseName, Duration) VALUES ('003', 'Tango', 50)
    INTO WorkoutIncludeExercise (WID, ExerciseName, Duration) VALUES ('004', 'Volleyball', 80)
    INTO WorkoutIncludeExercise (WID, ExerciseName, Duration) VALUES ('005', 'Swimming', 60)
    INTO WorkoutIncludeExercise (WID, ExerciseName, Duration) VALUES ('006', 'Burpees', 15)
    INTO WorkoutIncludeExercise (WID, ExerciseName, Duration) VALUES ('007', 'Jogging', 15)
SELECT * FROM dual;

INSERT ALL
    INTO MealContainFood (MID, FoodName) VALUES ('1', 'Medium Green Apple')
    INTO MealContainFood (MID, FoodName) VALUES ('2', 'Banana')
    INTO MealContainFood (MID, FoodName) VALUES ('3', 'Chicken Fried Thigh')
    INTO MealContainFood (MID, FoodName) VALUES ('4', 'Pizza')
    INTO MealContainFood (MID, FoodName) VALUES ('5', 'Black Coffee')
SELECT * FROM dual;

INSERT ALL
    INTO DoesWorkout (UserID, WID) VALUES ('001', '001')
    INTO DoesWorkout (UserID, WID) VALUES ('001', '002')
    INTO DoesWorkout (UserID, WID) VALUES ('002', '003')
    INTO DoesWorkout (UserID, WID) VALUES ('002', '002')
    INTO DoesWorkout (UserID, WID) VALUES ('003', '004')
    INTO DoesWorkout (UserID, WID) VALUES ('003', '005')
    INTO DoesWorkout (UserID, WID) VALUES ('005', '006')
SELECT * FROM dual;

INSERT ALL
    INTO HasNutrition (UserID, NID) VALUES ('001', '1')
    INTO HasNutrition (UserID, NID) VALUES ('001', '2')
    INTO HasNutrition (UserID, NID) VALUES ('002', '3')
    INTO HasNutrition (UserID, NID) VALUES ('003', '4')
    INTO HasNutrition (UserID, NID) VALUES ('003', '5')
SELECT * FROM dual;

INSERT ALL
    INTO HasMeal (UserID, MID) VALUES ('001', '1')
    INTO HasMeal (UserID, MID) VALUES ('001', '2')
    INTO HasMeal (UserID, MID) VALUES ('002', '3')
    INTO HasMeal (UserID, MID) VALUES ('003', '4')
    INTO HasMeal (UserID, MID) VALUES ('003', '5')
SELECT * FROM dual;

INSERT ALL
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('001', DATE '2022-01-01', 52.0, 167.6)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('001', DATE '2023-10-06', 58.5, 175)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('001', DATE '2023-10-12', 57.6, 175)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('001', DATE '2023-10-18', 57, 175)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('001', DATE '2023-11-08', 54, 175)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('001', DATE '2023-11-09', 55, 175)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('001', DATE '2023-11-14', 54.2, 175)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('001', DATE '2023-11-20', 53.3, 175)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('001', DATE '2023-11-21', 52.7, 175)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('002', DATE '2023-10-02', 73.4, 178.2)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('003', DATE '2022-01-03', 60.5, 172.3)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('004', DATE '2022-01-04', 86.0, 180.9)
    INTO TrackMeasurement (UserID, MDate, Weight, Height) VALUES ('005', DATE '2022-01-05', 100.0, 200.0)
SELECT * FROM dual;