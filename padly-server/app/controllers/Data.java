package controllers;

import com.google.common.base.Charsets;
import com.google.common.io.Files;
import play.*;
import play.mvc.*;

import views.html.*;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;


public class Data extends Controller {

    @BodyParser.Of(BodyParser.Json.class)
    public static Result monsters()
    {
        String data = "";
        File f = new File("data/monsters.txt");
        try
        {
            if (f.isFile())
            {
                data = Files.toString(f, Charsets.UTF_8);
            }
            else
            {
                throw new FileNotFoundException();
            }
        }
        catch (IOException e)
        {
            return status(500, e.getMessage());
        }

        return ok(data);
    }

    @BodyParser.Of(BodyParser.Json.class)
    public static Result activeSkills()
    {
        String data = "";
        File f = new File("data/active_skills.txt");
        try
        {
            if (f.isFile())
            {
                data = Files.toString(f, Charsets.UTF_8);
            }
            else
            {
                throw new FileNotFoundException();
            }
        }
        catch (IOException e)
        {
            return status(500, e.getMessage());
        }

        return ok(data);
    }
}
