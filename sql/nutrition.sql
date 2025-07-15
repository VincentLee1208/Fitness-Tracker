-- Food(FoodName, FoodCalories)
CREATE TABLE Food(
    FoodName VARCHAR(25) PRIMARY KEY,
    FoodCalories INTEGER DEFAULT 0 NOT NULL
);

-- Nutrition(NDate, DailyConsumedCalories, DailyCaloriesGoal)
CREATE TABLE Nutrition(
    NDate DATE PRIMARY KEY,
    DailyConsumedCalories INTEGER DEFAULT 0 NOT NULL,
    DailyCaloriesGoal INTEGER DEFAULT 0 NOT NULL
);

-- Workout(WID, NDate NOT NULL, TotalCaloriesBurned DEFAULT 0 NOT NULL, WorkoutDate NOT NULL, Total Duration NOT NULL)
CREATE TABLE Workout(
    WID VARCHAR(20) PRIMARY KEY,
	NDate DATE NOT NULL,
    TotalCaloriesBurned INTEGER DEFAULT 0 NOT NULL,
    WorkoutDate DATE NOT NULL,
    TotalDuration INTEGER NOT NULL,
    FOREIGN KEY (NDate) REFERENCES
        Nutrition(NDate)
        ON DELETE CASCADE
);

-- Meal(MID, NDate, Type, MealCaloriesConsumed)
CREATE TABLE Meal(
    MID VARCHAR(20)  PRIMARY KEY,
	NDate DATE NOT NULL,
    Type VARCHAR(10),
    MealCaloriesConsumed INTEGER DEFAULT 0 NOT NULL,
    FOREIGN KEY (NDate) REFERENCES
        Nutrition(NDate)
        ON DELETE CASCADE
);

-- MealContainFood(MID, FoodName)
CREATE TABLE  MealContainFood(
    MID VARCHAR(10),
    FoodName VARCHAR(25),
    Quantity INTEGER DEFAULT 0 NOT NULL,
    PRIMARY KEY(MID,FoodName),
    FOREIGN KEY(MID) REFERENCES
        Meal(MID)
        ON DELETE CASCADE,
    FOREIGN KEY (FoodName) REFERENCES
        Food(FoodName)
        ON DELETE CASCADE
);

INSERT ALL
    INTO Food(FoodName, FoodCalories) VALUES ('Medium Green Apple', 90)
    INTO Food(FoodName, FoodCalories) VALUES ('Banana', 90)
    INTO Food(FoodName, FoodCalories) VALUES ('Chicken Fried Thigh', 200)
    INTO Food(FoodName, FoodCalories) VALUES ('Pizza', 200)
    INTO Food(FoodName, FoodCalories) VALUES ('Black Coffee', 5)
SELECT * FROM dual;

INSERT ALL
    INTO Nutrition (NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES (DATE '2022-01-01', 1800, 2000)
    INTO Nutrition (NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES (DATE '2022-01-02', 1556, 1800)
    INTO Nutrition (NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES (DATE '2022-01-03', 1900, 1600)
    INTO Nutrition (NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES (DATE '2022-01-04', 2500, 2300)
    INTO Nutrition (NDate, DailyConsumedCalories, DailyCaloriesGoal)
    VALUES (DATE '2022-01-05', 2600, 2800)
SELECT * FROM dual;

INSERT ALL
    INTO Workout (NDate, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	(DATE '2022-01-01', '001', 0, DATE '2022-01-01', 0)
    INTO Workout (NDate, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	(DATE '2022-01-02', '002', 3005, DATE '2022-01-02', 120)
    INTO Workout (NDate, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	(DATE '2022-01-03', '003', 1928, DATE '2022-01-03', 36)
    INTO Workout (NDate, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	(DATE '2022-01-04', '004', 2394, DATE '2022-01-04', 65)
    INTO Workout (NDate, WID, TotalCaloriesBurned, WorkoutDate, TotalDuration)
    VALUES	(DATE '2022-01-05', '005', 230, DATE '2022-01-05', 15)
SELECT * FROM dual;

INSERT ALL
    INTO Meal (NDate, MID, Type, MealCaloriesConsumed) VALUES (DATE '2022-01-01', '1', 'Breakfast', 700)
    INTO Meal (NDate, MID, Type, MealCaloriesConsumed) VALUES (DATE '2022-01-02', '2', 'Snack', 200)
    INTO Meal (NDate, MID, Type, MealCaloriesConsumed) VALUES (DATE '2022-01-03', '3', 'Lunch', 900)
    INTO Meal (NDate, MID, Type, MealCaloriesConsumed) VALUES (DATE '2022-01-04', '4', 'Snack', 200)
    INTO Meal (NDate, MID, Type, MealCaloriesConsumed) VALUES (DATE '2022-01-05', '5', 'Dinner', 600)
SELECT * FROM dual;

INSERT ALL
    INTO MealContainFood (MID, FoodName) VALUES ('1', 'Medium Green Apple')
    INTO MealContainFood (MID, FoodName) VALUES ('2', 'Banana')
    INTO MealContainFood (MID, FoodName) VALUES ('3', 'Chicken Fried Thigh')
    INTO MealContainFood (MID, FoodName) VALUES ('4', 'Pizza')
    INTO MealContainFood (MID, FoodName) VALUES ('5', 'Black Coffee')
SELECT * FROM dual;