<?php
    require_once("mytemplate.php");
    require_once("DBConnection.php");

    //controller==================================
    if(isset($_POST['btnAdd']))  
    {
        entryForm();
    }
    else if(isset($_GET['actionEdit']))  
    {
        editForm();
    }
    else if(isset($_GET['actionDelete']))  
    {
        deleteRecord();
    }
    else if(isset($_POST['btnSave']))  
    {
        saveRecord();
    }
    else if(isset($_POST['btnUpdate']))  
    {
        updateRecord();
    }
    else
    {
        viewTable();
    }


    //display records=============================
    function viewTable()
    {      
      $tblRows="";
      $sql = "SELECT * FROM tbl_student ORDER BY LName,FName,MName;" ;
      $result = mysqli_query($GLOBALS['conn'], $sql);   
      $num_rows = mysqli_num_rows($result);
      
      // fetch each record from database
      if($num_rows > 0)
      {     
        for($ctr = 1; $ctr <= $num_rows; $ctr++)
        {
          $row = mysqli_fetch_assoc($result);
          $idno = $row['IDNo'];
          $lname = $row['LName'];
          $fname = $row['FName'];
          $mname = $row['MName'];
          $address=$row['Address'];
          $course = $row['Course'];
          $year = $row['Year'];
          
          $tblRows .= <<< htmlcode
                   <tr>
                      <td>$idno</td>
                      <td>$lname</td>
                      <td>$fname</td>
                      <td>$mname</td>
                      <td>$address</td>
                      <td>$course</td>
                      <td>$year</td>
                      
                      <td>
                        <a href="page1.php?actionEdit=$idno">
                            <i class="fas fa-edit"></i> 
                        </a> &nbsp; &nbsp;
                        
                        <a onclick="return confirm('Do you really want to delete the record with the following ID No. : $idno ?')"
                          href = "page1.php?actionDelete=$idno"> <i class="fas fa-minus"></i>
                        </a>
                      </td>

                    </tr>
htmlcode;
        }

      }
      else
      {

          $tblRows .= "<center> No records found. </center>";
      }


        $htmlCode = <<< myStringCode
             <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Student List</h3>
                </div>
              
              <!-- /.card-header -->

              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>ID No.</th>
                      <th>Lastname</th>
                      <th>Firstname</th>
                      <th>Middlename</th>
                      <th>Address</th>
                      <th>Course</th>
                      <th>Year</th>

                      <th> 
                        <form name="frmPage1" method="POST" action="page1.php">                       
                        <button type="submit" name="btnAdd" class="btn btn-default">
                          <i class="fas fa-plus"></i> Add New
                        </button>
                        </form>
                      </th>
                    </tr>
                  </thead>
                  <tbody>

                    $tblRows 
                   
                  </tbody>
                </table>
              </div>

              <!-- /.card-body -->
            </div>   
myStringCode;
    
      

        viewPage($htmlCode);
    }

    //data entry form=============================
    function entryForm()
    {

        $htmlCode = <<< myStringCode
        
                <div class="card card-primary">
                  <div class="card-header">
                    <h3 class="card-title">Add Student</h3>
                  </div>
                  <!-- /.card-header -->
                  <!-- form start -->
                  <form method="POST" action="page1.php">
                    <div class="card-body">
                      <div class="form-group">
                        <label >ID No. :</label>
                        <input type="text" name="txtIDNo" class="form-control" required maxlength="10">
                      </div>
                      <div class="form-group">
                        <label >Lastname :</label>
                        <input type="text" name="txtLName" class="form-control" required>
                      </div>
                      <div class="form-group">
                        <label >Firstname :</label>
                        <input type="text" name="txtFName" class="form-control" required>
                      </div>
                      
                      <div class="form-group">
                        <label >Middlename :</label>
                        <input type="text" name="txtMName" class="form-control" required>
                      </div>
                      
                      <div class="form-group">
                        <label >Address :</label>
                        <input type="text" name="txtAddress" class="form-control" required>
                      </div>
                      
                      <div class="form-group">
                        <label >Course :</label>
                        <input type="text" name="cmbCourse" class="form-control" required maxlength="10">
                      </div>
                      
                      <div class="form-group">
                        <label >Year :</label>
                        <input type="text" name="cmbYear" class="form-control" required maxlength="3">
                      </div>
                      
                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer">
                      <button type="submit" name="btnSave" class="btn btn-primary">Save</button>
                    </div>
                  </form>
                </div>
                <!-- /.card -->       

myStringCode;
        
         viewPage($htmlCode);
    }


    //data edit form=============================
    function editForm()
    {
      $idno= $_GET["actionEdit"];
      $sql = "SELECT * FROM tbl_student WHERE IDNo='$idno';" ;
      $result = mysqli_query($GLOBALS['conn'], $sql);   
      $row = mysqli_fetch_assoc($result);
      $idno = $row['IDNo'];
      $lname = $row['LName'];
      $fname = $row['FName'];
      $mname = $row['MName'];
      $address=$row['Address'];
      $course = $row['Course'];
      $year = $row['Year'];

        $htmlCode = <<< myStringCode
        
                <div class="card card-primary">
                  <div class="card-header">
                    <h3 class="card-title">Add Student</h3>
                  </div>
                  <!-- /.card-header -->
                  <!-- form start -->
                  <form method="POST" action="page1.php">
                    <div class="card-body">
                      <div class="form-group">
                        <label >ID No. :</label>
                        <input type="hidden" name="txtOldIDNo" value="$idno">
                        <input type="text" name="txtIDNo" class="form-control" value="$idno" required>
                      </div>
                      <div class="form-group">
                        <label >Lastname :</label>
                        <input type="text" name="txtLName" class="form-control" value="$lname" required>
                      </div>
                      <div class="form-group">
                        <label >Firstname :</label>
                        <input type="text" name="txtFName" class="form-control" value="$fname" required>
                      </div>
                      
                      <div class="form-group">
                        <label >Middlename :</label>
                        <input type="text" name="txtMName" class="form-control" value="$mname" required>
                      </div>
                      
                      <div class="form-group">
                        <label >Address :</label>
                        <input type="text" name="txtAddress" class="form-control" value="$address" required>
                      </div>
                      
                      <div class="form-group">
                        <label >Course :</label>
                        <input type="text" name="cmbCourse" class="form-control" value="$course" required >
                      </div>
                      
                      <div class="form-group">
                        <label >Year :</label>
                        <input type="text" name="cmbYear" class="form-control" value="$year" required maxlength="3">
                      </div>
                      
                    </div>
                    <!-- /.card-body -->

                    <div class="card-footer">
                      <button type="submit" name="btnUpdate" class="btn btn-primary">Update</button>
                    </div>
                  </form>
                </div>
                <!-- /.card -->       

myStringCode;
        
         viewPage($htmlCode);
    }

    //save record================================
    function saveRecord()
    {
        $idno=$_POST["txtIDNo"];
        $lname=$_POST["txtLName"];
        $fname=$_POST["txtFName"];
        $mname=$_POST["txtMName"];
        $address=$_POST["txtAddress"];
        $course=$_POST["cmbCourse"];
        $year=$_POST["cmbYear"];

        $sql = "SELECT * FROM tbl_student WHERE IDNo='$idno';" ;
        $result = mysqli_query($GLOBALS['conn'], $sql);   
        $num_rows = mysqli_num_rows($result);
        if($num_rows==0)
        {
          $sql = "INSERT INTO tbl_student(IDNo, LName, FName, MName,Address,Course,Year) " .
                 " VALUES('$idno','$lname','$fname','$mname','$address','$course','$year');";
          $result = mysqli_query($GLOBALS['conn'],$sql);
          
          viewTable();
        }
        else
        {
          entryForm();
        }
    }

    //update record==============================
    function updateRecord()
    {
        $oldidno=$_POST["txtOldIDNo"];
        $newidno=$_POST["txtIDNo"];
        $lname=$_POST["txtLName"];
        $fname=$_POST["txtFName"];
        $mname=$_POST["txtMName"];
        $address=$_POST["txtAddress"];
        $course=$_POST["cmbCourse"];
        $year=$_POST["cmbYear"];

        $sql = "SELECT * FROM tbl_student WHERE IDNo='$idno';" ;
        $result = mysqli_query($GLOBALS['conn'], $sql);   
        $num_rows = mysqli_num_rows($result);

        if(($num_rows==0 && $oldidno != $idno) || 
           ($num_rows>0 && $oldidno == $idno))
        {
          $sql = "UPDATE tbl_student SET IDNo='$newidno', LName='$lname', FName='$fname', MName='$mname', Address='$address', Course='$course', Year='$year' WHERE IDNo='$oldidno' ;";

          $result = mysqli_query($GLOBALS['conn'],$sql);
          
          viewTable();
        }
        else
        {
          header("Location:page1.php?actionEdit=$oldidno");
        }
    }

    //delete record==============================
    function deleteRecord()
    {
        $idno=$_GET['actionDelete'];
        $sql = "DELETE FROM tbl_student WHERE IDNo='$idno';";
        $result = mysqli_query($GLOBALS['conn'],$sql);
        
        viewTable();
    }

    
?>
